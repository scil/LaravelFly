<?php

namespace LaravelFly\Coroutine;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\ProviderRepository;

class RequestApplication extends \LaravelFly\Coroutine\BaseApplication
{
    /**
     * @var int
     */
    var $coroutineID;

    /**
     * @var \LaravelFly\Coroutine\Application
     */
    var $workerApplication;
    /**
     * @var array
     */
    private $providers;

    public function __construct($coroutineID)
    {
        $this->workerApplication = static::$instance;
        $this->coroutineID = $coroutineID;
        $this->providers = $this->workerApplication->getProvidersInRequest();

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

    public function registerConfiguredProviders()
    {
        // todo  which first?
//        $providers = Collection::make($this->config['app.providers'])
//            ->partition(function ($provider) {
//                return Str::startsWith($provider, 'Illuminate\\');
//            });

        $manifestPath = $this->getCachedServicesPathInRequest();
        (new ProviderRepositoryInRequest($this, new Filesystem, $manifestPath))->makeManifest($this->providers)->load([]);
    }

    public function boot()
    {
        if ($this->bootedOnWorker) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->bootedOnWorker = true;

        //todo it should be changed
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function make($abstract, array $parameters = [])
    {
        if(in_array($abstract,['app',\Illuminate\Foundation\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class,  \Psr\Container\ContainerInterface::class] )){
           return static::getInstance();
        }
        //todo  event

        if ($this->workerApplication->resolved($abstract)) {
            return $this->workerApplication->make($abstract, $parameters);
        } else {
            return parent::make($abstract, $parameters);
        }
    }

    /**
     * Override
     */
    public function runningInConsole()
    {
        if (defined('HONEST_IN_CONSOLE')) {
            return HONEST_IN_CONSOLE;
        } else {
            return parent::runningInConsole();
        }
    }

}