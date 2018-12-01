<?php

namespace LaravelFly\Server;

use LaravelFly\Tools\TablePipe\PlainFilePipe;
use swoole_atomic;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

// in testing, this is necessary because const mapFlyFiles use LARAVELFLY_SERVICES
if (!defined('LARAVELFLY_SERVICES')) include __DIR__ . '/../../../config/laravelfly-server-config.example.php';

class Common
{
    use Traits\DispatchRequestByQuery;
    use Traits\Preloader;
    use Traits\Tinker;
    use Traits\Worker;
    use Traits\Laravel;
    use Traits\ShareInteger;
    use Traits\ShareTable;
    use Traits\Console;
    use Traits\Task;

    /**
     * where laravel app located
     * @var string
     */
    protected $root;

    /**
     * @var array
     */
    protected $options;

    static $flyBaseDir ;


    const mapFlyFiles = [
        'Container.php' =>
            '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
        'Application.php' =>
            '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
        'ServiceProvider.php' =>
            '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
        'Routing/RouteDependencyResolverTrait.php' =>
            '/vendor/laravel/framework/src/Illuminate/Routing/RouteDependencyResolverTrait.php',
        'Routing/Router.php' =>
            '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
        'ViewConcerns/ManagesComponents.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesComponents.php',
        'ViewConcerns/ManagesLayouts.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLayouts.php',
        'ViewConcerns/ManagesLoops.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLoops.php',
        'ViewConcerns/ManagesStacks.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesStacks.php',
        'ViewConcerns/ManagesTranslations.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesTranslations.php',
        'Facade.php' =>
            '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',

        /**
         * otherwise
         * on each boot of PaginationServiceProvider and NotificationServiceProvider,
         * view paths would be appended to app('view')->finder->hints by  $this->loadViewsFrom again and again
         */
        'FileViewFinder' . (LARAVELFLY_SERVICES['view.finder'] ? 'SameView' : '') . '.php' =>
            '/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php',


        // experimental, no speed gained
//        'Database/Eloquent/Concerns/HasRelationships.php'
//        => '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasRelationships.php',

        'Database/Eloquent/Model.php'
        => '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    ];

    /**
     * fly files included conditionally.
     * this array is only for
     * test tests/Map/Feature/FlyOfficialFilesTest.php
     *
     * @var array
     */
    protected static $conditionFlyFiles = [
        'log_cache' => [
            'StreamHandler.php' =>
                '/vendor/monolog/monolog/src/Monolog/Handler/StreamHandler.php',
        ],
        'config' => [
            'Config/Repository.php' =>
                '/vendor/laravel/framework/src/Illuminate/Config/Repository.php'

        ],
        'bus' => [
            'Bus/Dispatcher.php' =>
                '/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php'

        ],
        'task' => [
            'Foundation/Bus/Dispatchable.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Bus/Dispatchable.php',
            'Foundation/Bus/PendingDispatch.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Bus/PendingDispatch.php',
            'Foundation/Support/Providers/EventServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Support/Providers/EventServiceProvider.php'
        ],
        'kernel' => [
            // '../Kernel.php' =>
            // match the dir structure of tests/offcial_files
            'Http/Kernel.php' =>
                '/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php'

        ],
        'request' => [
            'symfony/Request.php' => '/vendor/symfony/http-foundation/Request.php',
            'RequestConcerns/InteractsWithInput.php' => '/vendor/laravel/framework/src/Illuminate/Http/Concerns/InteractsWithInput.php',
            'Request.php' => '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
            'UrlGenerator.php' => '/vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php',
            'RouteUrlGenerator.php' => '/vendor/laravel/framework/src/Illuminate/Routing/RouteUrlGenerator.php',
        ],
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
     * @var string
     */
    protected $appClass = '\LaravelFly\Map\Application';

    /**
     * @var string
     */
    protected $kernelClass = \LaravelFly\Kernel::class;


    protected $colorize = true;


    /**
     * log message level
     *
     * 0: ERR
     * 1: ERR, WARN
     * 2: ERR, WARN, NOTE
     * 3: ERR, WARN, NOTE, INFO
     */
    protected $echoLevel = 3;

    public function __construct(EventDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();

        $this->console();

        $this->root = dirname(__DIR__, 6);

        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} \n");
        }

        static::$flyBaseDir = dirname(__DIR__, 4) . '/laravel-fly-files/src/';

    }


    public function config(array $options)
    {

        if (empty($options['mode'])) $options['mode'] = LARAVELFLY_MODE;

        $this->options = array_merge($this->getDefaultConfig(), $options);

        $this->parseOptions($this->options);

    }

    public function getDefaultConfig()
    {
        // why @ ? For the errors like : Constant LARAVELFLY_MODE already defined
        $d = @ include __DIR__ . '/../../../config/laravelfly-server-config.example.php';

        return array_merge([
            'mode' => 'Map',
            'conf' => null, // server config file
        ], $d);
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

        static::includeFlyFiles($options);

        include_once __DIR__ . '/../../MidKernel.php';

        // as earlier as possible
        if ($options['pre_include'])
            $this->preInclude();

        if (isset($options['pid_file'])) {
            $options['pid_file'] .= '-' . $options['listen_port'];
        } else {
            $options['pid_file'] = $this->root . '/bootstrap/laravel-fly-' . $options['listen_port'] . '.pid';
        }

        $appClass = $options['application'] ?? '\LaravelFly\\' . $options['mode'] . '\Application';

        if (class_exists($appClass)) {
            $this->appClass = $appClass;
        } else {
            $this->echo("Mode in config file not valid, no appClass $appClass\n", 'WARN', true);
        }

        $kernelClass = $options['kernel'] ?? 'App\Http\Kernel';
        if (!(
            (LARAVELFLY_MODE === 'Backup' && is_subclass_of($kernelClass, \LaravelFly\Backup\Kernel::class)) ||
            (LARAVELFLY_MODE === 'Map' && is_subclass_of($kernelClass, \LaravelFly\Map\Kernel::class))
        )) {

            $kernelClass = \LaravelFly\Kernel::class;
            $this->echo(
                "LaravelFly default kernel used: $kernelClass, 
      please edit App/Http/Kernel like https://github.com/scil/LaravelFly/blob/master/doc/config.md",
                'WARN', true
            );
        }
        $this->kernelClass = $kernelClass;

        $this->prepareTinker($options);

        $this->dispatchRequestByQuery($options);


        $this->sessionTable();
    }

    static function includeFlyFiles(&$options)
    {
        if (LARAVELFLY_MODE === 'FpmLike') return;

        $flyBaseDir  = static::$flyBaseDir;

        // all fly files are for Mode Map, except Config/BackupRepository.php for Mode Backup
        include_once $flyBaseDir . 'Config/' . (LARAVELFLY_MODE === 'Map' ? '' : 'Backup') . 'Repository.php';

        static $mapLoaded = false, $logLoaded = false;

        if ($options['mode'] === 'Map' && !$mapLoaded) {

            $mapLoaded = true;

            if (empty(LARAVELFLY_SERVICES['kernel'])) {
                $localFlyBaseDir = __DIR__ . '/../../fly/';
                include_once $localFlyBaseDir . 'Kernel.php';
            }

            if (empty(LARAVELFLY_SERVICES['bus']))
                static::includeConditionFlyFiles( 'bus');


            // if ((LARAVELFLY_SERVICES['request']))
                static::includeConditionFlyFiles( 'request');

            foreach (static::mapFlyFiles as $f => $offical) {
                require $flyBaseDir . $f;
            }

        }


        if (!$logLoaded) {

            $logLoaded = true;

            if (is_int($options['log_cache']) && $options['log_cache'] > 1) {

                foreach (static::$conditionFlyFiles['log_cache'] as $f => $offical) {
                    require $flyBaseDir . $f;
                }

            } else {

                $options['log_cache'] = false;
            }
        }

    }

    protected static function includeConditionFlyFiles( $key)
    {

        foreach (static::$conditionFlyFiles[$key] as $f => $offical) {
            require static::$flyBaseDir . $f;
        }
    }

    static function getFlyMap()
    {
        $r = static::mapFlyFiles;

        foreach (static::$conditionFlyFiles as $map) {
            $r = array_merge($r, $map);
        }
        return $r;
    }


    protected function sessionTable()
    {
        $back = $this->getConfig('swoole_session_back');

        if (!$back) return;

        $this->dispatcher->addListener('server.prestart', function (GenericEvent $event) use ($back) {

            if (in_array($back, ['redis', 'apc', 'memcached'])) {
                //todo
                $this->swooleSessionTable->init(new PlainFilePipe(
                    $this->swooleSessionTable,
                    $this->root . '/storage/framework/swooleSessions.txt'
                ));
            } else {
                $this->swooleSessionTable->init(new PlainFilePipe(
                    $this->swooleSessionTable,
                    $this->root . '/storage/framework/swooleSessions.txt'
                ));
            }

            $this->getTableMemory('swooleSession')->restore();
        });

        $this->dispatcher->addListener('server.stopped', function (GenericEvent $event) {
            $this->getTableMemory('swooleSession')->dump();
        });


    }

    public function createSwooleServer(): \swoole_http_server
    {
        $options = $this->options;

        if ($options['early_laravel']) $this->startLaravel();

        if ($this->options['daemonize'])
            $this->colorize = false;

        if ($this->options['echo_level'])
            $this->echoLevel = (int)$this->options['echo_level'];

        $this->swoole = $swoole = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $options['enable_coroutine'] = false;

        $swoole->set($options);

        $this->setListeners();

        $swoole->fly = $this;

        return $swoole;
    }

    public function setListeners()
    {
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);

        $this->swoole->on('WorkerStop', [$this, 'onWorkerStop']);

        $this->swoole->on('Shutdown', [$this, 'onShutdown']);

        $this->swoole->on('Start', [$this, 'onStart']);

        $this->initForTask();

    }


    function onStart(\swoole_http_server $server)
    {
        $this->echo("pid: " . $server->manager_pid . '; master pid: ' . $server->master_pid);
    }

    function onShutdown(\swoole_http_server $server)
    {
        $this->echo("event server.stopped");

        $this->dispatcher->dispatch('server.stopped',
            new GenericEvent(null, ['server' => $this]));

    }

    public function start()
    {
        if (is_callable($this->options['prestart_func'])) {

            $this->echo("run prestart_func");

            $this->options['prestart_func']->call($this);

        }

        if (!method_exists('\Co', 'getUid'))
            die("[ERROR] pecl install swoole or enable swoole.use_shortname.\n");


        if ($this->getConfig('watch_down')) {

            $this->newIntegerMemory('isDown', new swoole_atomic(0));
        }

        $this->echo("event server.prestart");

        $this->dispatcher->dispatch('server.prestart',
            new GenericEvent(null, ['server' => $this]));

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

    public function path($path = null): string
    {
        return $path ? "{$this->root}/$path" : $this->root;
    }



}