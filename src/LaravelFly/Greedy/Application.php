<?php

namespace LaravelFly\Greedy;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\Simple\ProviderRepository;

class Application extends \LaravelFly\Simple\Application
{

    protected $bootedOnWorker = false;

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