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
     * An laravel application instance living always with a worker.
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
        $this->appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';
        $this->kernelClass = $options['kernel'] ?? '';
        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} ");
        }

    }

    public function onWorkerStart()
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