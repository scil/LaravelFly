<?php

namespace LaravelFly\Greedy;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\Normal\ProviderRepository;

class Application extends \LaravelFly\Normal\Application
{

    protected $bootedOnWorker = false;

    /**
     * * * * * * * * * * * * * * * * * * * * * * *
     * follow attributes are not used by Coroutine mode
     * * * * * * * * * * * * * * * * * * * * * * *
     */
    protected $providersToBootOnWorker = [];


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




    /**
     * * * * * * * * * * * * * * * * * * * * * * *
     * follow methods are not used by Coroutine mode
     * * * * * * * * * * * * * * * * * * * * * * *
     */
    public function setProvidersToBootOnWorker($ps)
    {
        $this->providersToBootOnWorker = $ps;
    }

    public function registerConfiguredProvidersBootOnWorker()
    {
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathBootOnWorker()))
            ->load($this->providersToBootOnWorker);
    }

    public function getCachedServicesPathBootOnWorker()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_on_worker.json';
    }
    public function resetServiceProviders()
    {
        $this->serviceProviders = [];
    }
    public function registerProvidersAcross()
    {
        $config = $this->make('config');
        $providers = array_diff(
        // providers in request have remove from 'app.providers'
            $config->get('app.providers'),
            $this->providersToBootOnWorker
        );

        if ($providers) {
            if ($config->get('app.debug')) {
                echo PHP_EOL, 'Providers across ( reg on work and boot on request )', PHP_EOL, __CLASS__, PHP_EOL;
                var_dump($providers);
            }

            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPathAcross()))
                ->load($providers);

        }
    }
    public function getCachedServicesPathAcross()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_across.json';
    }

}