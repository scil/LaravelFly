<?php

namespace LaravelFly\Server;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Common
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
     * @var array
     */
    protected $defaultOptions = [
        'mode'=>'Map',
        'server' => 'LaravelFly\\Server\\HttpServer',
        'daemonize' => false,
        'tinker' => false,
        'dispatch_by_query' => false,
        'listen_ip' => '0.0.0.0',
        'listen_port' => 9501,
        'worker_num' => 5,
        'max_request' => 1000,
        'max_coro_num' => 3000,
        'daemonize' => false,
        'watch' => [],
        'watch_delay' => 3500,
        'compile' => true,
        'compile_files' => [],
    ];

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
        if(defined('LARAVELFLY_MODE')) $options['mode']=LARAVELFLY_MODE;

        $this->options = array_merge($this->defaultOptions, $options);

        $event = new GenericEvent(null, ['server' => $this, 'options' => $this->options]);
        $this->dispatcher->dispatch('server.config', $event);

        // so options can be changed by event handler
        $this->options = $event['options'];

        $this->parseOptions($this->options);

        echo '[INFO] server options parsed', PHP_EOL;
    }

    public function getConfig($name = null)
    {
        if (is_string($name)) {
            return $this->options[$name] ?? null;
        }
        return $this->options;

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

        $this->appClass = '\LaravelFly\\' . $options['mode'] . '\Application';
        if (!class_exists($this->appClass)) {
            die("[ERROR] Mode set in config file not valid\n");
        }

        $kernelClass = $options['kernel'] ?? 'App\Http\Kernel';
        if (!(
            is_subclass_of($kernelClass, \LaravelFly\Simple\Kernel::class) ||
            is_subclass_of($kernelClass, \LaravelFly\Map\Kernel::class))) {
            $kernelClass = \LaravelFly\Kernel::class;
            echo "[WARN] kernel: $kernelClass", PHP_EOL;
        }
        $this->kernelClass = $kernelClass;

        $this->prepareTinker($options);

        $this->dispatchRequestByQuery($options);
    }

    public function createSwooleServer():\swoole_http_server
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


    public function start()
    {
        $this->setMemory('isDown',new \swoole_atomic(0));

        try {

            $this->swoole->start();

        } catch (\Throwable $e) {
            die("[ERROR] swoole server started failed: {$e->getMessage()} \n");
        }
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    public function getSwooleServer(): \swoole_server
    {
        return $this->swoole;
    }

    public function path($path = null):string
    {
        return $path ? "{$this->root}/$path" : $this->root;
    }

    public function getMemory(string $name)
    {
        return $this->memory[$name] ?? null;
    }

    /**
     * @param array $memory
     */
    public function setMemory($name, $value)
    {
        $this->memory[$name] = $value;
    }

}