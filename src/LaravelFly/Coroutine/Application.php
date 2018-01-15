<?php

namespace LaravelFly\Coroutine;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\Normal\ProviderRepository;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Application extends \LaravelFly\Application
{

    use \LaravelFly\ApplicationTrait;

    protected $bootedOnWorker = false;

    protected $bootedInRequest=false;
    protected $acrossServiceProviders=[];

    /**
     * @var array all application instances live now in current worker
     */
    protected static $self_instances = [];

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

        // $this->registerBaseBindings();
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
//        $this->instance(PackageManifest::class, new PackageManifest(
//            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
//        ));

//        $this->register(new EventServiceProvider($this));
//        $this->register(new RoutingServiceProvider($this));
    }
    function delRequestApplication($coroutineID)
    {
        unset(static::$self_instances[$coroutineID]);
    }
    public static function getInstance()
    {
        $cID = \Swoole\Coroutine::getuid();
        if (empty(static::$self_instances[$cID])) {
            //todo
//            static::$self_instances[$cID] = new static;
        }
        return static::$self_instances[$cID];
    }

    public static function setInstance(ContainerContract $container = null)
    {
        return static::$self_instances[\Swoole\Coroutine::getuid()] = $container;
    }
    function getInstances(){
        return static::$self_instances;
    }

    public function setProvidersToBootOnWorker($ps)
    {
    }
    public function registerAcrossProviders()
    {
        $config=$this->config;
        $providers = array_diff(
        // providers in request have remove from 'app.providers'
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

    public function registerConfiguredProvidersBootOnWorker($providers)
    {
        $this->config = $this['config'];
        $this->providersToBootOnWorker = array_keys($providers);

        //todo study official registerConfiguredProviders
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathBootOnWorker()))
            ->load($this->providersToBootOnWorker);
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
        $this->loadDeferredProviders();

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
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
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