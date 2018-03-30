<?php

namespace LaravelFly\Server;

use LaravelFly\Exception\LaravelFlyException as Exception;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

Trait Common
{
    use Traits\DispatchRequestByQuery;
    use Traits\Preloader;
    use Traits\Tinker;
    use Traits\Worker;
    use Traits\Laravel;

    /**
     * where laravel app located
     * @var string
     */
    protected $root;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var \swoole_http_server
     */
    var $swoole;

    /**
     * @var array save any shared info across processes
     */
    var $memory = [];

    /**
     * @var string
     */
    protected $appClass;

    /**
     * @var string
     */
    protected $kernelClass;


    public function __construct($dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();

        $this->root = realpath(__DIR__ . '/../../../../../..');

        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} \n");
        }

    }

    public function config(array $options)
    {
        $this->options = $options;

        $this->parseOptions($options);

        $event = new GenericEvent(null, ['server' => $this, 'options' => $options]);
        $this->dispatcher->dispatch('server.config', $event);

        $this->options = $event['options'];

        echo '[INFO] server options ready', PHP_EOL;
    }

    public function create()
    {
        $options = $this->options;

        $this->swoole = $swoole = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $swoole->set($options);

        $this->setListeners();

        $swoole->fly = $this;

        $event = new GenericEvent(null, ['server' => $this, 'options' => $options]);
        $this->dispatcher->dispatch('server.created', $event);

        printf("[INFO] server %s created\n", static::class);
    }

    protected function parseOptions(array &$options)
    {
        // as earlier as possible
        if ($options['compile'] !== false)
            $this->loadCachedCompileFile();

        if (isset($options['pid_file'])) {
            $options['pid_file'] .= '-' . $options['listen_port'];
        } else {
            $options['pid_file'] = $this->root . '/bootstrap/laravel-fly-' . $options['listen_port'] . '.pid';
        }

        $this->appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';
        if (!class_exists($this->appClass)) {
            die("Mode set in config file not valid\n");
        }

        $kernelClass = $options['kernel'] ?? \App\Http\Kernel::class;
        if (!(
            is_subclass_of($kernelClass, \LaravelFly\Simple\Kernel::class) ||
            is_subclass_of($kernelClass, \LaravelFly\Map\Kernel::class))) {
            $kernelClass = \LaravelFly\Kernel::class;
        }
        $this->kernelClass = $kernelClass;
        echo "[INFO] kernel: $kernelClass", PHP_EOL;

        $this->prepareTinker($options);

        $this->dispatchRequestByQuery($options);
    }

    public function getSwooleServer(): \swoole_server
    {
        return $this->swoole;
    }

    public function path($path = null)
    {
        return $path ? "{$this->root}/$path" : $this->root;
    }

    public function start()
    {
        try {

            $this->memory['isDown'] = new \swoole_atomic(0);

            $this->swoole->start();

        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }


}