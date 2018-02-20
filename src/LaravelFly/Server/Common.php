<?php

namespace LaravelFly\Server;

use function foo\func;
use LaravelFly\Exception\LaravelFlyException as Exception;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

Trait Common
{
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
     * where laravel app located
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $appClass;

    /**
     * @var string
     */
    protected $kernelClass;

    /**
     * For APP_TYPE=='worker', an laravel application instance living always with a worker, not the server.
     *
     * In Mode Dict, it can't be made living always with the server,
     * because most of Dict-Friendly Services are made only by \Swoole\Coroutine::getuid()
     * without using swoole_server::$worker_id, they can not distinguish coroutines in different workers.
     *
     * @var \LaravelFly\Dict\Application|\LaravelFly\Simple\Application|\LaravelFly\Greedy\Application
     */
    protected $app;

    /**
     * An laravel kernel instance living always with a worker.
     *
     * @var \LaravelFly\Dict\Kernel|\LaravelFly\Simple\Kernel|\LaravelFly\Greedy\Kernel
     */
    protected $kernel;

    static $workerIds = [];

    public function __construct(array $options, $dispatcher = null)
    {

        $this->options = $options;

        $this->dispatcher = $dispatcher ?: new EventDispatcher();
    }

    public function create()
    {
        $options = $this->options;

        $this->parseOptions($options);

        $event = new GenericEvent(null, ['server' => $this, 'options' => $options]);
        $this->dispatcher->dispatch('server.creating', $event);
        // then listeners can change options
        $options = $this->options = $event['options'];

        $this->swoole = $swoole = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $swoole->set($options);

        $this->setListeners();

        $swoole->fly = $this;

        $event = new GenericEvent(null, ['server' => $this]);
        $this->dispatcher->dispatch('server.created', $event);
    }

    protected function parseOptions(array &$options)
    {

        $this->root = realpath(__DIR__ . '/../../../../../..');
        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} ");
        }

        if (isset($options['pid_file'])) {
            $options['pid_file'] .= '-' . $options['listen_port'];
        } else {
            $options['pid_file'] = $this->root . '/bootstrap/laravel-fly-' . $options['listen_port'] . '.pid';
        }

        $this->appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';

        $this->kernelClass = $options['kernel'] ?? \App\Http\Kernel::class;

        $this->createWorkerIds($options);

        $this->prepareTinker($options);

        $this->dispatchByQuery($options);
    }

    protected function createWorkerIds($options)
    {
        static::$workerIds = $table = new \swoole_table($options['worker_num']);

        $table->column('id', \swoole_table::TYPE_INT, 1);
        $table->column('pid', \swoole_table::TYPE_INT, 3);
        $table->create();
        $this->workerIdsSubscriber();

    }

    function workerIdsSubscriber()
    {
        $this->dispatcher->addListener('worker.starting', function (GenericEvent $event) {
            static::$workerIds->set($event['workerid'], ['id' => $event['workerid'], 'pid' => getmypid()]);
        });
        $this->dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
            static::$workerIds->del($event['workerid']);
        });
    }

    protected function prepareTinker(&$options)
    {

        if (empty($options['tinker'])) return;

        if ($options['daemonize'] == true) {
            $options['daemonize'] = false;
            echo '[INFO] daemonize is disabled to let tinker run normally', PHP_EOL;
        }

        if ($options['worker_num'] == 1) {
            echo '[INFO] worker_num is 1, your server can not response any other requests when using shell', PHP_EOL;
        }

        $this->tinkerSubscriber();

    }

    function tinkerSubscriber()
    {

        $this->dispatcher->addListener('worker.starting', function (GenericEvent $event) {
            \LaravelFly\Tinker\Shell::make($event['server']);

            \LaravelFly\Tinker\Shell::addAlias([
                \LaravelFly\LaravelFly::class,
            ]);
        });

        $this->dispatcher->addListener('app.created', function (GenericEvent $event) {
            $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
        });

    }

    protected function dispatchByQuery(&$options)
    {
        if (empty($options['dispatch_by_query'])) return;

        if ($options['worker_num'] == 1) {
            echo '[INFO] worker_num is 1, dispatch_by_query is useless', PHP_EOL;
            return;
        }

        if (isset($options['dispatch_func'])) {
            echo '[INFO] dispatch_func is set, dispatch_by_query is disabled', PHP_EOL;
            return;
        }

        $options['dispatch_func'] = function ($serv, $fd, $type, $data) {
            if (preg_match('/worker-(id|pid)=(\d+)/i', $data, $matches)) {
                if ($matches[1] == 'id') {
                    return intval($matches[2]) % $serv->setting['worker_num'];
                } else {
                    foreach ($serv->fly->getWorkerIds() as $row) {
                        if ($row['pid'] == $matches[2]) {
                            return $row['id'];
                        }
                    }
                }
                return $fd % $serv->setting['worker_num'];
            }
        };

    }

    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        opcache_reset();

        $event = new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id]);
        $this->dispatcher->dispatch('worker.starting', $event);
    }

    public function onWorkerStop(\swoole_server $server, int $worker_id)
    {
        $event = new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id]);
        $this->dispatcher->dispatch('worker.stopped', $event);
    }

    public function getSwooleServer(): \swoole_server
    {
        return $this->swoole;
    }

    /**
     * @return \LaravelFly\Dict\Application|\LaravelFly\Greedy\Application|\LaravelFly\Simple\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    public function getAppType()
    {
        return $this::APP_TYPE;
    }

    function getWorkerIds()
    {
        return static::$workerIds;
    }

    public function path($path = null)
    {
        return $path ? "{$this->root}/$path" : $this->root;
    }

    public function start()
    {
        try {
            $this->swoole->start();
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function startLaravel()
    {

        $this->app = $app = new $this->appClass($this->root);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            $this->kernelClass
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);


        // the 'request' here is different form FpmHttpServer
        $event = new GenericEvent(null, ['server' => $this, 'app' => $app, 'request' => null]);
        $this->dispatcher->dispatch('app.created', $event);

    }

    /**
     * convert swoole request info to php global vars
     *
     * only for Mode One or Greedy
     *
     * @param \swoole_http_request $request
     * @see https://github.com/matyhtf/framework/blob/master/libs/Swoole/Request.php setGlobal()
     */
    protected function setGlobal($request)
    {
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_FILES = $request->files ?? [];
        $_COOKIE = $request->cookie ?? [];

        $_SERVER = array();
        foreach ($request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }

        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);

        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
    }

    /**
     * produce swoole response from laravel response
     *
     * @param \swoole_http_response $response
     * @param $laravel_response
     */
    protected function swooleResponse(\swoole_http_response $response, $laravel_response): void
    {
        foreach ($laravel_response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        foreach ($laravel_response->headers->getCookies() as $cookie) {
            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        $response->status($laravel_response->getStatusCode());

        // gzip use nginx
        // $response->gzip(1);

        $response->end($laravel_response->getContent());
    }

}