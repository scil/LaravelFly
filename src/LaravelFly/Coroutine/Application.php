<?php

namespace LaravelFly\Coroutine;

use LaravelFly\Coroutine\Illuminate\EventServiceProvider;
use LaravelFly\Coroutine\Illuminate\RoutingServiceProvider;
use Illuminate\Log\LogServiceProvider;

use Illuminate\Filesystem\Filesystem;
use LaravelFly\One\ProviderRepository;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Application extends \LaravelFly\Application
{

    /**
     * @var bool
     */
    protected $bootedOnWorker = false;

    /**
     * @var bool
     */
    protected $bootedInRequest=false;

    /**
     * @var array
     */
    protected $acrossServiceProviders=[];

    /**
     * The id of coroutine which this instance is in
     *
     * @var int
     */
    protected $coid;

    /**
     * if this application instance is a worker app or a request app.
     *
     * the worker app is always $appInstance->instance or Container::$instance
     *
     * @var bool
     */
    protected $isRequestApp;

    public function __construct($basePath = null)
    {
        parent::__construct($basePath);
        $this->isRequestApp = false;
        $this->coid = \Swoole\Coroutine::getuid();
        static::$instance = $this;
    }


    function __clone()
    {
        $this->isRequestApp = true;
        $this->coid = \Swoole\Coroutine::getuid();

        /**
         * following is implementing part of  parent __construct
         */

        // replace $this->registerBaseBindings();
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        //todo:
//        $this->instance(PackageManifest::class, new PackageManifest(
//            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
//        ));

        /**
         * replace $this->register(new EventServiceProvider($this));
         */
        $this->instance('events',  clone $this->make('events'));
        /**
         * replace $this->register(new RoutingServiceProvider($this));
         * @todo obj.routes need clone too?
         */
        $this->instance('router',  clone $this->make('router'));
    }

    static function delRequestApplication($coroutineID)
    {
        unset(static::$self_instances[$coroutineID]);
    }

    public function registerAcrossProviders()
    {
        $config=$this->make('config');
        $providers = array_diff(
            // providers in request have remove from 'app.providers' by CleanProviders
            $config->get('app.providers'),
            $this->providersToBootOnWorker
        );

        $serviceProviders = $this->serviceProviders ;
        $this->serviceProviders = [];

        if ($providers) {
            if ($config->get('app.debug')) {
                echo PHP_EOL, 'start to reg Providers across', PHP_EOL, __CLASS__, PHP_EOL;
                var_dump($providers);
            }

            //todo update code
            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathAcross()))
                ->load($providers);

        }

        $this->acrossServiceProviders=$this->serviceProviders;
        //todo merge? nest?
        $this->serviceProviders = array_merge($serviceProviders, $this->serviceProviders);
    }

    public function setProvidersToBootOnWorker($providers)
    {
        $this->providersToBootOnWorker = array_keys($providers);
    }
    public function registerConfiguredProvidersBootOnWorker()
    {

        //todo study official registerConfiguredProviders
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathBootOnWorker()))
            ->load($this->providersToBootOnWorker);

        $this->loadDeferredProviders();
    }
    public function getCachedServicesPathBootOnWorker()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_on_worker.json';
    }
    public function getCachedServicesPathAcross()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_across.json';
    }
    public function bootOnWorker()
    {

        if ($this->bootedOnWorker) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->bootedOnWorker = true;

        /**
         * moved to {@link bootInRequest()}
         */
        // $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function bootInRequest()
    {
        if ($this->bootedInRequest) {
            echo 'has booted ', PHP_EOL;
            return;
        }

        $this->registerConfiguredProvidersInRequest();

        /**
         * moved to {@link bootOnWorker()}
         */
        // $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->acrossServiceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->bootedInRequest= true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /*
     * Override
     * use new providers for
     * 1. new services with __clone
     * 2. compiled all routes which are made before request
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new LogServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));
    }

    public function make($abstract, array $parameters = [])
    {
        if (in_array($abstract, ['app', \Illuminate\Foundation\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class])) {
            return static::getInstance();
        }
        //todo  event

        return parent::make($abstract, $parameters);
    }

}