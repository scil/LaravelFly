<?php


class LaravelFlyServer
{
    /**
     * @var LaravelFlyServer
     */
    protected static $instance;

    /**
     * where laravel app located
     * @var string
     */
    protected $root;

    /**
     * @var swoole_http_server
     */
    public $swoole_http_server;

    /**
     * An laravel application instance living always with a worker.
     *
     * @var \LaravelFly\Coroutine\Application|\LaravelFly\One\Application|\LaravelFly\Greedy\Application
     */
    protected $workerApp;

    /**
     * An laravel kernel instance living always with a worker.
     *
     * @var \LaravelFly\Coroutine\Kernel|\LaravelFly\One\Kernel|\LaravelFly\Greedy\Kernel
     */
    protected $workerKernel;

    /**
     * @param array $options
     * @return LaravelFlyServer|null
     */
    public function __construct(array $options)
    {

        $this->root = realpath(__DIR__ . '/../../../..');
        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} ");
        }

        if (isset($options['pid_file'])) {
            $options['pid_file'] .= $options['listen_port'];
        } else {
            $options['pid_file'] = $this->root . '/bootstrap/laravelfly.pid' . $options['listen_port'];
        }

        $this->swoole_http_server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);

        $server->on('WorkerStart', array($this, 'onWorkerStart'));

        $server->on('request', array($this, 'onRequest'));

        return $this;


    }

    /**
     * @param array $options
     * @return LaravelFlyServer|null
     */
    public static function getInstance($options)
    {
        if (!self::$instance) {
            try {
                self::$instance = new static($options);
            } catch (\Throwable $e) {
                die('[FAILED] ' . $e->getMessage() . PHP_EOL);
            }
        }
        return self::$instance;
    }

    public function start()
    {
        try {
            $this->swoole_http_server->start();
        } catch (\Throwable $e) {
            die('[FAILED] ' . $e->getMessage() . PHP_EOL);
        }

        $this->initSthWhenServerStart();
    }

    /**
     * Do sth. that is done in all of the Laravel requests.
     * @see Illuminate\Foundation\Http\Kernel::handle()
     */
    protected function initSthWhenServerStart()
    {
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }

    public function onWorkerStart()
    {

        $appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';

        $this->workerApp = new $appClass($this->root);

        $this->workerApp->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LARAVELFLY_KERNEL
        );
        $this->workerApp->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->workerKernel = $this->workerApp->make(\Illuminate\Contracts\Http\Kernel::class);

        $this->bootstrapOnWorkerStart();

    }

    /**
     * instance a fake request then bootstrap
     *
     * new UrlGenerator need a request.
     * In Mode One, no worry about it's fake, because
     * app['url']->request will update when app['request'] changes, as rebinding is used
     * <code>
     * <?php
     * $url = new UrlGenerator(
     *  $routes, $app->rebinding(
     *      'request', $this->requestRebinder()
     *  )
     * );
     * ?>
     *  "$app->rebinding( 'request',...)"
     * </code>
     * @see  Illuminate\Routing\RoutingServiceProvider::registerUrlGenerator()
     *
     */
    protected function bootstrapOnWorkerStart()
    {

        $this->workerApp->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));

        try {
            $this->workerKernel->bootstrap();
        } catch (\Throwable $e) {
            echo $e;
            $this->swoole_http_server->shutdown();
        }

    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        if (LARAVELFLY_MODE == 'Coroutine') {

            $cid=\Swoole\Coroutine::getuid();

            $laravel_request = (new \LaravelFly\Coroutine\IlluminateBase\Request())->createFromSwoole($request);

            $this->workerApp->initForCorontine($cid);

            $requestKernel = clone $this->workerKernel;

            $laravel_response = $requestKernel->handle($laravel_request);

        } else {

            /**
             * @see Symfony\Component\HttpFoundation\Request::createFromGlobals() use global vars, and
             * this static method is alse used by Illuminate\Auth\Guard
             */
            $this->setGlobal($request);

            /**
             * @var Illuminate\Http\Request
             * @see Illuminate\Http\Request::capture
             */
            $laravel_request = \Illuminate\Http\Request::createFromBase(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

            /**
             * @var Illuminate\Http\Response
             * @see Illuminate\Foundation\Http\Kernel::handle
             */
            $laravel_response = $this->workerKernel->handle($laravel_request);
        }

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


        if (LARAVELFLY_MODE == 'Coroutine') {

            $requestKernel->terminate($laravel_request, $laravel_response);

            $this->workerApp->delForCoroutine($cid);

        } else {

            $this->workerKernel->terminate($laravel_request, $laravel_response);

            $this->workerApp->restoreAfterRequest();

        }
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
}

