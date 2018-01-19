<?php

namespace LaravelFly\Coroutine;

use Illuminate\Support\ServiceProvider;
use LaravelFly\Coroutine\IlluminateBase\EventServiceProvider;
use LaravelFly\Coroutine\IlluminateBase\RoutingServiceProvider;
use Illuminate\Log\LogServiceProvider;

use Illuminate\Filesystem\Filesystem;
use LaravelFly\One\ProviderRepository;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Application extends \Illuminate\Foundation\Application
{

    use \LaravelFly\Application;

    /**
     * @var bool
     */
    protected $bootedOnWorker = false;

    /**
     * @var bool
     */
    protected $bootedInRequest = [];

    /**
     * @var array
     */
    protected $providersToBootOnWorker=[];

    /**
     * @var array
     */
    protected $acrossServiceProviders = [];

    protected $arrayAttriForObj=['resolved','bindings','methodBindings','instances','aliases','abstractAliases','extenders','tags','buildStack','with','contextual','reboundCallbacks','globalResolvingCallbacks','globalAfterResolvingCallbacks','resolvingCallbacks','afterResolvingCallbacks',
        'bootingCallbacks','bootedCallbacks','terminatingCallbacks','serviceProviders','loadedProviders','deferredServices'
    ];
    protected $normalAttriForObj=[
        'hasBeenBootstrapped'=>false,'booted'=>false,
        'bootedInRequest'=>false,
    ];

    public function __construct($basePath = null)
    {
        parent::__construct($basePath);
        static::$instance = $this;
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

    public function initForCorontine($cid)
    {
        parent::initForCorontine($cid);

        /**
         * replace $this->register(new RoutingServiceProvider($this));
         *
         * in most cituations, routes clone is not needed, but it's possbile that
         * in a request a service may add more routes.
         * If so , the array content of routes vars will grow and grow.
         *
         * order is important, because dependencies:
         *  router : routes
         *  url : routes
         */

        /**
         *
         * url is not needed to implement __clone() method, because it's  attributes will updated auto.
         * so it should be before routes.  router should be after routes, because it use __clone,no $app->rebinding
         *
         * @var \Illuminate\Routing\UrlGenerator
         * @see \Illuminate\Routing\RoutingServiceProvider::registerUrlGenerator()
         * @todo test
         */
        if($cid>0){
            ServiceProvider::initForCorontine($cid);
            $this->make('events')->initForCorontine($cid);
            $this->instance('url', clone $this->make('url'));
            $this->instance('routes', clone $this->make('routes'));
            $this->instance('router', clone $this->make('router'));
        }
    }

    function delForCoroutine(int $cid)
    {
        $this->make('events')->delForCoroutine($cid);
        ServiceProvider::delForCoroutine($cid);
        //this should be the last line, otherwise $this->make('events') can not work
        parent::delForCoroutine($cid);
    }

    public function setProvidersToBootOnWorker($providers)
    {
        $this->providersToBootOnWorker = $providers;
    }

    public function registerAcrossProviders()
    {
        $cid=\Swoole\Coroutine::getuid();
        $config = $this->make('config');
        $providers = array_diff(
            // providers in request have remove from 'app.providers' by CleanProviders
            $config->get('app.providers'),
            $this->providersToBootOnWorker
        );

        $serviceProvidersBack = $this->serviceProviders[$cid];
        $this->serviceProviders[$cid] = [];

        if ($providers) {

            //todo update code
            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathAcross()))
                ->load($providers);

        }

        $this->acrossServiceProviders = $this->serviceProviders[$cid];
        $this->serviceProviders[$cid] = $serviceProvidersBack;
    }

    public function getCachedServicesPathAcross()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_across.json';
    }

    public function registerConfiguredProvidersBootOnWorker()
    {

        //todo study official registerConfiguredProviders
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathBootOnWorker()))
            ->load($this->providersToBootOnWorker);

        //todo
        $this->loadDeferredProviders();
    }

    public function getCachedServicesPathBootOnWorker()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_on_worker.json';
    }

    public function bootOnWorker()
    {

        $cid=\Swoole\Coroutine::getuid();
        if ($this->bootedOnWorker) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks[$cid]);

        array_walk($this->serviceProviders[$cid], function ($p) {
            $this->bootProvider($p);
        });

        $this->bootedOnWorker = true;

        /**
         * moved to {@link bootInRequest()}
         */
        // $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function resetServiceProviders()
    {
        $this->serviceProviders[\Swoole\Coroutine::getuid()] = [];
    }

    public function bootInRequest()
    {
        $cid=\Swoole\Coroutine::getuid();
        if ($this->bootedInRequest[$cid]) {
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
        array_walk($this->serviceProviders[$cid], function ($p) {
            $this->bootProvider($p);
        });

        $this->bootedInRequest[$cid] = $this->booted[$cid] = true;

        $this->fireAppCallbacks($this->bootedCallbacks[$cid]);
    }
    public function make($abstract, array $parameters = [])
    {
        if (in_array($abstract, ['app', \Illuminate\Foundation\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class])) {
            return static::getInstance();
        }

        return parent::make($abstract, $parameters);
    }

    public function addDeferredServices(array $services)
    {
        $cid=\Swoole\Coroutine::getuid();
        $this->deferredServices[$cid] = array_merge($this->deferredServices[$cid], $services);
    }
}