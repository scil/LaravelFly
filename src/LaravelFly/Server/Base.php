<?php

namespace LaravelFly\Server;

class Base
{

    protected $server;

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

    public function __construct(array &$options)
    {

        $this->root = realpath(__DIR__ . '/../../../../../..');
        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} ");
        }

        $this->appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';
        $this->kernelClass = $options['kernel'] ?? '';

        if (isset($options['pid_file'])) {
            $options['pid_file'] .= '-' . $options['listen_port'];
        } else {
            $options['pid_file'] = $this->root . '/bootstrap/laravel-fly-' . $options['listen_port'] . '.pid';
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

    }

    public function start()
    {
        try {
            $this->server->start();
        } catch (\Throwable $e) {
            die('[FAILED] ' . $e->getMessage() . PHP_EOL);
        }

        $this->initAfterStart();
    }

}