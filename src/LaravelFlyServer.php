<?php


class LaravelFlyServer
{
    protected static $instance;
    protected $laravelDir;
    protected $compiledPath;
    public $swoole_http_server;
    protected $app;
    protected $kernelClass;
    protected $kernel;

    public function __construct($laravelDir, $options, $kernelClass='\App\Http\Kernel')
    {

        $this->laravelDir = realpath($laravelDir);
        $this->compiledPath = $this->laravelDir . 'bootstrap/cache/compiled.php';

        if (LOAD_COMPILED_BEFORE_WORKER) {
            $this->loadCompiledAndInitSth();
        }

        $this->swoole_http_server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $this->kernelClass=$kernelClass;

        $server->set($options);

        $server->on('WorkerStart', array($this, 'onWorkerStart'));

        $server->on('request', array($this, 'onRequest'));

        return $this;
    }
    public function start()
    {
        $this->swoole_http_server->start();
    }

    protected function loadCompiledAndInitSth()
    {

        if (file_exists($this->compiledPath)) {
            require $this->compiledPath;
        }

        // removed from Illuminate\Foundation\Http\Kernel::handle
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();

    }

    public function onWorkerStart()
    {

        if (!LOAD_COMPILED_BEFORE_WORKER) {
            $this->loadCompiledAndInitSth();
        }

        $this->app = $app = LARAVELFLY_GREEDY ?
            new \LaravelFly\Greedy\Application($this->laravelDir) :
            new \LaravelFly\Application($this->laravelDir);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LARAVELFLY_KERNEL
        );
        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        $this->bootstrap();

    }

    protected function bootstrap()
    {


        // App\Providers\RouteServiceProvider.boot app['url'] which need app['request']
        // app['url']->request will update when app['request'] changes, becuase
        // there is "$app->rebinding( 'request',...)" at Illuminate\Routing\RoutingServiceProvider
        if (LARAVELFLY_GREEDY) {
            $this->app->instance('request', $this->getFakeRequest());
        }

        $this->kernel->bootstrap();


    }

    protected function getFakeRequest()
    {
        static $request = null;
        if (is_null($request)) {
            $request = \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request());
        }
        return $request;

    }


    public function onRequest($request, $response)
    {
        // static files
        try{
            $try_file = $this->laravelDir.'/public'.$request->server['request_uri'];
            if(file_exists($try_file)){
                $mtime = filemtime($try_file);

                if($request->header['if-none-match']??-1 == $mtime){
                    $response->status(304);
                    $response->header('ETag', $mtime);
                    $response->end();
                    return $this->app->restoreAfterRequest();
                }
                $response->status(200);
                $response->header('ETag', $mtime);
                $response->header('Cache-Control', 'max-age=600');
                $response->header('Content-Type', explode(',', $request->header['accept']??'application/x-javascript,')[0]);
                $response->end(file_get_contents($try_file));
                return $this->app->restoreAfterRequest();
            }
        }catch(\Exception $e){
            $response->end();
            return $this->app->restoreAfterRequest();
        }


        // global vars used by: Symfony\Component\HttpFoundation\Request::createFromGlobals()
        // this static method is alse used by Illuminate\Auth\Guard
        $this->setGlobal($request);

        // according to : Illuminate\Http\Request::capture
        // static::enableHttpMethodParameterOverride(); // this line moved to $this->bootstrap() :
        $laravel_request = \Illuminate\Http\Request::createFromBase(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

        // see: Illuminate\Foundation\Http\Kernel::handle($request)
        $laravel_response = $this->kernel->handle($laravel_request);


        //  sometimes there are errors saying 'http_onReceive: connection[...] is closed' and this type of error make worker restart
        if (!$this->swoole_http_server->exist($response->fd)) {
            return;
        }

        foreach ($laravel_response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        foreach ($laravel_response->headers->getCookies() as $cookie) {
            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        // I think " $l_response->send()" is enough
        // $response->status($l_response->getStatusCode());

        // gzip use nginx
        // $response->gzip(1);

        ob_start();
        // $laravel_response->send() contains setting header and cookie ,and $response->header and $response->cookie do same jobs.
        // They are all necessary , according by my test
        $laravel_response->send();
        $response->end(ob_get_clean());


        $this->kernel->terminate($laravel_request, $laravel_response);


        $this->app->restoreAfterRequest();

    }

    // copied from Swoole Framework
    // https://github.com/swoole/framework/blob/master/libs/Swoole/Http/ExtServer.php
    // Swoole Framework is a web framework like Laravel
    protected function setGlobal($request)
    {
        if (isset($request->get)) {
            $_GET = $request->get;
        } else {
            $_GET = array();
        }
        if (isset($request->post)) {
            $_POST = $request->post;
        } else {
            $_POST = array();
        }
        if (isset($request->files)) {
            $_FILES = $request->files;
        } else {
            $_FILES = array();
        }
        if (isset($request->cookie)) {
            $_COOKIE = $request->cookie;
        } else {
            $_COOKIE = array();
        }
        if (isset($request->server)) {
            $_SERVER = $request->server;
        } else {
            $_SERVER = array();
        }
        //todo: necessary?
        foreach ($_SERVER as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
            unset($_SERVER[$key]);
        }
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
        $_SERVER['REQUEST_URI'] = $request->server['request_uri'];

        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
        $_SERVER['REMOTE_ADDR'] = $request->server['remote_addr'];
    }

    public static function getInstance($laravelDir, $options)
    {
        if (!self::$instance) {
            self::$instance = new static($laravelDir, $options);
        }
        return self::$instance;
    }
}

