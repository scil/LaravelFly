<?php

namespace LaravelFly\Coroutine;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\Normal\ProviderRepository;

class Application extends \LaravelFly\Coroutine\BaseApplication
{

    use \LaravelFly\ApplicationTrait;

    protected $bootedOnWorker = false;
    protected $providersInRequest;
    protected $requestApps = [];
    protected $singles = [];

    public function __construct($basePath = null)
    {
        parent::__construct($basePath);
        static::$instance = $this;
    }

    public function setProvidersInRequest($ps)
    {
        $this->providersInRequest = $ps;
    }

    public function getProvidersInRequest()
    {
        return $this->providersInRequest ?? [];
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

        foreach ($this->singles as $abstract) {
            echo "make single $abstract  \n";
            //todo
            if (!in_array($abstract, ['filesystem.cloud',]))
                $this->make($abstract);
        }

        //todo it should be changed
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
        $this->singles[] = $abstract;
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


    function createRequestApplication($coroutineID)
    {
        return $this->requestApps[$coroutineID] = new RequestApplication($coroutineID);
    }

    function delRequestApplication($coroutineID)
    {
        unset($this->requestApps[$coroutineID]);
        unset(static::$self_instances[$coroutineID]);
    }

}