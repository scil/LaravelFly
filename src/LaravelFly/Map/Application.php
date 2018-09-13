<?php

namespace LaravelFly\Map;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LaravelFly\Map\IlluminateBase\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Log\LogServiceProvider;

use Illuminate\Filesystem\Filesystem;
use LaravelFly\Backup\ProviderRepository;

class Application extends \Illuminate\Foundation\Application
{

    use \LaravelFly\ApplicationTrait\ProvidersInRequest;
    use \LaravelFly\ApplicationTrait\InConsole;
    use \LaravelFly\ApplicationTrait\Server;
    use \LaravelFly\ApplicationTrait\NoMorePackageManifest;

    /**
     * @var bool
     */
    protected $bootedOnWorker = false;

    /**
     * @var string[]
     */
    protected $providersToBootOnWorker = [];

    /**
     * @var \Illuminate\Support\ServiceProvider[]
     */
    protected $acrossServiceProviders = [];

    /**
     * only for method: getProviders($provider)
     *
     * @var \Illuminate\Support\ServiceProvider[]
     */
    protected $backedServiceProviders = [];

    /**
     * @var string[]
     */
    protected $CFServices = [];

    /**
     * @var string[]
     */
    protected $cloneServices = [];

    /**
     * @var array[]
     */
    protected $updateOnRequest = [];

    protected static $arrayAttriForObj = ['resolved', 'bindings', 'methodBindings', 'instances', 'aliases', 'abstractAliases', 'extenders', 'tags', 'contextual', 'reboundCallbacks', 'globalResolvingCallbacks', 'globalAfterResolvingCallbacks', 'resolvingCallbacks', 'afterResolvingCallbacks',

        // no refactor for coroutine
        // 'buildStack',
        // 'with',
        //
        //this prop, bootingCallbacks's refactor is made in fly\Application,
        //but not made for Map\Application(as it only used onworker)
        // 'bootingCallbacks',

        'bootedCallbacks',
        'terminatingCallbacks',
        'serviceProviders',
        'loadedProviders',
        'deferredServices'
    ];
    protected static $normalAttriForObj = [
        'hasBeenBootstrapped' => false,
        'booted' => false,
    ];

    protected $bootingCallbacks = [];

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

    public function initForRequestCorontine($cid)
    {
        parent::initForRequestCorontine($cid);

        /**
         * replace $this->register(new RoutingServiceProvider($this));
         *
         * order is important, because relations:
         *  router.routes
         *  url.routes
         */

        /**
         *
         * url is not needed to implement __clone() method, because it's  attributes will updated auto.
         * so it should be before routes which is cloned by {@link \Illuminate\Routing\Router::initForRequestCorontine } .
         *
         * @see \Illuminate\Routing\RoutingServiceProvider::registerUrlGenerator()
         * @todo test
         */
        Facade::initForRequestCorontine($cid);
        $this->make('events')->initForRequestCorontine($cid);
        // make an new url object which is independent from origin url object
        $this->instance('url', clone $this->make('url'));
        $this->make('router')->initForRequestCorontine($cid);

        $this->make('events')->dispatch('request.corinit', [$cid]);

        foreach ($this->cloneServices as $service) {
            $this->instance($service, clone $this->make($service));
        }
        foreach ($this->updateOnRequest as $item) {
            $item['closure']->call(empty($item['this']) ? null : $this->make($item['this']));
        }
    }

    function unsetForRequestCorontine(int $cid)
    {

        $this->make('events')->dispatch('request.corunset', [$cid]);

        $this->make('router')->unsetForRequestCorontine($cid);

        Facade::unsetForRequestCorontine($cid);

        //this should be the last second, events maybe used by anything, like dispatch 'request.corunset'
        $this->make('events')->unsetForRequestCorontine($cid);

        //this should be the last line, otherwise $this->make('events') can not work
        parent::unsetForRequestCorontine($cid);

    }

    public function setProvidersToBootOnWorker(array $providers)
    {
        $this->providersToBootOnWorker = $providers;
    }

    public function setCFServices(array $services)
    {
        $this->CFServices = $services;
    }

    public function setCloneServices(array $services, array $update)
    {
        $this->cloneServices = $services;

        $this->updateOnRequest = $update;
    }

    public function registerAcrossProviders()
    {

        // after LoadConfiguration,  'app.providers' only hold across providers
        if ($providers = $this->make('config')->get('app.providers')) {
            $serviceProvidersBack = static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'];
            static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'] = [];

            //todo update code
            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathAcross()))
                ->load($providers);

            $this->backedServiceProviders = $this->acrossServiceProviders =
                static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'];

            // foreach ($this->backedServiceProviders as $p) {echo get_class($p), "\n";}

            static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'] = $serviceProvidersBack;
        }

    }

    public function getCachedServicesPathAcross(): string
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_across.json';
    }

    public function registerWorkerProviders()
    {

        $providers = Collection::make($this->providersToBootOnWorker)
            ->partition(function ($provider) {
                return Str::startsWith($provider, ['Illuminate\\', 'LaravelFly\\']);
            });

        $providerRepository = new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathBootOnWorker());

        $providerRepository->loadForWorker($providers->collapse()->toArray());

    }

    public function getCachedServicesPathBootOnWorker(): string
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_on_worker.json';
    }

    /**
     * please use bootOnWorker or bootInRequest instead
     */
    public function boot()
    {

    }

    public function bootOnWorker()
    {

        if ($this->bootedOnWorker) {
            return;
        }

        // $this->fireAppCallbacks(static::$corDict[WORKER_COROUTINE_ID]['bootingCallbacks']);
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk(static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'], function ($p) {
//            print_r(get_class($p));echo " -- \n";
            $this->bootProvider($p);
        });

        $this->bootedOnWorker = true;

        /**
         * moved to {@link bootInRequest()}
         */
        // $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function makeCFServices()
    {
        foreach ($this->CFServices as $service) {
            if ($this->bound($service)) {
                $this->make($service);
            } else {
                \LaravelFly\Fly::getServer()->echo("$service not bound", 'NOTE', true);
            }
        }
    }

    public function instanceResolvedOnWorker($abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset(static::$corDict[WORKER_COROUTINE_ID]['instances'][$abstract]);

    }

    /**
     * for the proper execution of registerConfiguredProvidersInRequest
     */
    public function resetServiceProviders()
    {
        $this->backedServiceProviders = array_merge(
            static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'],
            $this->backedServiceProviders
        );

        //foreach ($this->backedServiceProviders as $p) {echo get_class($p), "\n";}

        static::$corDict[WORKER_COROUTINE_ID]['serviceProviders'] = [];
    }

    /**
     * overwrite
     * because resetServiceProviders
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where(
            array_merge(
                $this->backedServiceProviders,
                static::$corDict[\Swoole\Coroutine::getuid()]['serviceProviders']
            ),
            function ($value) use ($name) {
                return $value instanceof $name;
            });
    }

    public function bootInRequest()
    {
        $cid = \Co::getUid();
        if (static::$corDict[$cid]['booted']) {
            return;
        }

        $this->registerConfiguredProvidersInRequest();

        /**
         * moved to {@link bootOnWorker()}
         */
        // $this->fireAppCallbacks($this->bootingCallbacks);

        // make a new array var, because it maybe changed by array_walk
        $across = $this->acrossServiceProviders;

        array_walk($across, function ($p) {
            $this->bootProvider($p);
        });

        array_walk(static::$corDict[$cid]['serviceProviders'], function ($p) {
            $this->bootProvider($p);
        });

        static::$corDict[$cid]['booted'] = true;

        $this->fireAppCallbacks(static::$corDict[$cid]['bootedCallbacks']);
    }

    public function addDeferredServices(array $services)
    {
        $cid = \Co::getUid();
        static::$corDict[$cid]['deferredServices'] = array_merge(static::$corDict[$cid]['deferredServices'], $services);
    }

    /**
     * if a middleware can live in a whole of worker
     * @param $name string
     * @param $whitelist array
     * @return object|null
     */
    public function getStableMiddlewareInstance($name, $whitelist)
    {
        /*
         * avoid middlewares with parameters because the execution of obj middleware do not support parameters defined with ':'
         * see: Pipleline::carry()
         *      $parameters = [$passable, $stack];
         */
        if (mb_strpos($name, ':') !== false) {
            //todo add test
            return null;
        }

        $concrete = $this->make($name);

        if (in_array($name, $whitelist)) {
            return $concrete;
        }

        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$reflector->isInstantiable()) {
            return $concrete;
        }

        $constructor = $reflector->getConstructor();

        // no constructor
        if ($constructor === null) {
            return $concrete;
        }


        // no parameters?  but there maybe app->make() in the body of the constructor
//        $dependencies = $constructor->getParameters();
//        if (!$dependencies) return $concrete;

        // no more going into the parameters
//        foreach ($dependencies as $dependency) {
//            $c = $dependency->getClass();
//        }

        return null;

    }

    static $singletonMiddlewares = [];

    public function setSingletonMiddlewares(array $middlewares)
    {
        self::$singletonMiddlewares = $middlewares;
    }

    /**
     * @param array $middlewares Kernel::middleware, not route middlewares
     * @param array $termi save terminate middlewares
     * @return array mix of objects(getStableMiddlewareInstance) and strings(can not stable)
     */
    function parseKernelMiddlewares($middlewares, &$termi): array
    {

        return array_map(function ($name) use (&$termi) {
            if ($this->getStableMiddlewareInstance($name, static::$singletonMiddlewares)) {

                $instance = $this->app->make($name);
                if (method_exists($instance, 'terminate')) {
                    $termi[] = $instance;
                }
                return $instance;
            }
            $termi[] = $name;
            return $name;
        }, $middlewares);

    }

    /**
     * hack: Cache for terminateMiddleware objects.
     * @param  \Illuminate\Http\Request $request
     * @return array  mixed of objects(middlwares's instances) and strings(middleware's name)
     */
    function gatherRouteTerminateMiddleware($request): array
    {
        if ($route = $request->route()) {
            return $this->router->gatherRouteMiddleware($route, true);
        }

        return [];
    }


    //BEGIN for bootingCallbacks
    //the prop, bootingCallbacks's refactor is made for Map\Application, but kept in fly\Application
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function flush()
    {
        parent::flush();
        $cid = \Co::getUid();

        $this->buildStack = [];
        static::$corDict[$cid]['loadedProviders'] = [];
        static::$corDict[$cid]['bootedCallbacks'] = [];
        // static::$corDict[$cid]['bootingCallbacks'] = [];
        $this->bootingCallbacks = [];
        static::$corDict[$cid]['deferredServices'] = [];
        static::$corDict[$cid]['reboundCallbacks'] = [];
        static::$corDict[$cid]['serviceProviders'] = [];
        static::$corDict[$cid]['resolvingCallbacks'] = [];
        static::$corDict[$cid]['afterResolvingCallbacks'] = [];
        static::$corDict[$cid]['globalResolvingCallbacks'] = [];
    }
    //END for bootingCallbacks


}