<?php

namespace LaravelFly\Coroutine;

use LaravelFly\Coroutine\Illuminate\EventServiceProvider;
use LaravelFly\Coroutine\Illuminate\RoutingServiceProvider;
use Illuminate\Log\LogServiceProvider;

use Illuminate\Filesystem\Filesystem;
use LaravelFly\Normal\ProviderRepository;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Application extends \LaravelFly\Application
{

    protected $bootedOnWorker = false;

    protected $bootedInRequest=false;
    protected $acrossServiceProviders=[];


    /**
     * @var int
     */
    protected $coroutineID;

    protected $isWorkerApplication = true;

    /**
     * @var \LaravelFly\Coroutine\Application
     */
    protected $workerApplication;

    /**
     * @var array
     */

    // should only run for worker application , not cloned application
    public function __construct($basePath = null)
    {
        parent::__construct($basePath);
        $this->isWorkerApplication = true;
        $this->coroutineID = \Swoole\Coroutine::getuid();
        static::$instance = $this;
    }


    function __clone()
    {
        $this->isWorkerApplication = false;
        $this->workerApplication = static::$instance;
        $this->coroutineID = \Swoole\Coroutine::getuid();

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

        // replace $this->register(new EventServiceProvider($this));
        $this->instance('events',  clone $this->make('events'));
        // replace $this->register(new RoutingServiceProvider($this));
        // todo : obj.routes need clone too?
        $this->instance('router',  clone $this->make('router'));
    }
    function delRequestApplication($coroutineID)
    {
        unset(static::$self_instances[$coroutineID]);
    }
//    function getInstances(){
//        return static::$self_instances;
//    }

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

            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathAcross()))
                ->load($providers);

        }

        $this->acrossServiceProviders=$this->serviceProviders;
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

//        foreach ($this->singles as $abstract) {
//            //todo
//            if (!in_array($abstract, ['filesystem.cloud',]))
//                $this->make($abstract);
//        }

        //todo it should be changed
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function bootInRequest()
    {
        if ($this->bootedInRequest) {
            echo 'has booted ', PHP_EOL;
            return;
        }

        $this->registerConfiguredProvidersInRequest();

        //todo it should be changed
        $this->fireAppCallbacks($this->bootingCallbacks);

        echo 'boot ', PHP_EOL;
        array_walk($this->acrossServiceProviders, function ($p) {
           echo 'boot ', get_class($p),PHP_EOL;
            $this->bootProvider($p);
        });

        $this->bootedInRequest= true;

        //todo it should be changed
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /*
     * Override
     * only for compiled all routes which are made before request
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