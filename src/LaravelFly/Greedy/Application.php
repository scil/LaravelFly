<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/7/29
 * Time: 22:10
 */

namespace LaravelFly\Greedy;

use Illuminate\Events\EventServiceProvider;
use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use Illuminate\Filesystem\Filesystem;
use LaravelFly\ProviderRepository;

class Application extends \LaravelFly\Application
{

    protected $bootedOnWorker = false;

    protected $providers_to_boot_in_worker = [];

    public function setProvidersToBootInWorker()
    {
        $appConfig = $this['config'];

        $common = array_intersect($appConfig['app.providers'], $appConfig['laravelfly.providers_in_worker']);

        $this->providers_to_boot_in_worker = $common;
    }

    public function registerConfiguredProvidersBootInWorker()
    {
        $manifestPath = $this->getCachedServicesPathBootInWorker();

        (new ProviderRepository($this, new Filesystem, $manifestPath))
            ->load($this->providers_to_boot_in_worker);
    }

    public function getCachedServicesPathBootInWorker()
    {
        return $this->basePath() . '/bootstrap/cache/laravelfly_services_in_worker.json';
    }

    public function resetServiceProviders()
    {
        $this->serviceProviders = [];
    }

    public function registerConfiguredProvidersAcross()
    {
        $config=$this->make('config');
        $providers = array_diff(
            // providers in request have remove from 'app.providers'
            $config->get('app.providers'),
            $this->providers_to_boot_in_worker
        );

        if ($providers) {
            if ($config->get('app.debug')) {
                echo PHP_EOL, 'Providers across ( reg on work and boot on request )', PHP_EOL, __CLASS__, PHP_EOL;
                var_dump($providers);
            }

            $manifestPath = $this->getCachedServicesPathAcross();
            (new ProviderRepository($this, new Filesystem, $manifestPath))
                ->load($providers);

        }
    }

    public function getCachedServicesPathAcross()
    {
        return $this->basePath() . '/bootstrap/cache/laravelfly_services_across.json';
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

        $this->fireAppCallbacks($this->bootedCallbacks);
    }


    /*
     * Override
     * only for compiled all routes which are made before request
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));
    }
}