<?php

namespace LaravelFly\Server;

use LaravelFly\Exception\LaravelFlyException as Exception;

Trait Common
{

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
     * An laravel application instance living always with a worker, not the server.
     *
     * In Mode Coroutine, it can't be made living always with the server,
     * because most of Coroutine-Friendly Services are made only by \Swoole\Coroutine::getuid()
     * without using swoole_server::$worker_id, they can not distinguish coroutines in different workers.
     *
     * @var \LaravelFly\Coroutine\Application|\LaravelFly\Simple\Application|\LaravelFly\Greedy\Application
     */
    protected $app;

    /**
     * An laravel kernel instance living always with a worker.
     *
     * @var \LaravelFly\Coroutine\Kernel|\LaravelFly\Simple\Kernel|\LaravelFly\Greedy\Kernel
     */
    protected $kernel;

    public function parseOptions(array &$options)
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

        if (LARAVELFLY_TINKER) {

            if ($options['daemonize'] == true) {
                $options['daemonize'] = false;
                echo '[INFO] daemonize is disabled in Mode FpmLike.', PHP_EOL;
            }

            if ($options['worker_num'] == 1) {
                echo '[INFO] worker_num is 1, your server can not response any other requests when using shell', PHP_EOL;
            }
        }

    }

    public function path($path = null)
    {
        return $path ? "{$this->root}/$path" : $this->root;
    }

    public function start()
    {
        try {
            $this->server->start();
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function startLaravel()
    {

        $this->app = new $this->appClass($this->root);

        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            $this->kernelClass
        );
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);


        $this->initTinker($this->app);

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

    protected function initTinker($app = null)
    {
        if (!LARAVELFLY_TINKER) return;

        \LaravelFly\Tinker\Shell::make($this);

        \LaravelFly\Tinker\Shell::addAlias([
            \LaravelFly\LaravelFly::class,
        ]);

        if ($app) {
            $this->withTinker($app);
        }
    }

    protected function withTinker(\Illuminate\Foundation\Application $app = null)
    {
        if (!LARAVELFLY_TINKER) return;

        $shell = \LaravelFly\Tinker\Shell::$instance;
        $app = $app ?: $this->app;
        $app->instance('tinker', $shell);

    }
}