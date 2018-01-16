<?php

namespace LaravelFly;

use Illuminate\Events\EventServiceProvider;
//use LaravelFly\Routing\RoutingServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use LaravelFly\Normal\ProviderRepositoryInRequest;

class Application extends \Illuminate\Foundation\Application
{
    protected $providerRepInRequest;

    public function makeManifestForProvidersInRequest($providers)
    {
        $manifestPath = $this->getCachedServicesPathInRequest();
        $this->providerRepInRequest = new ProviderRepositoryInRequest($this, new Filesystem, $manifestPath);
        $this->providerRepInRequest->makeManifest($providers);
    }

    public function getCachedServicesPathInRequest()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_in_request.json';
    }


    public function registerConfiguredProvidersInRequest()
    {
        if ($this->providerRepInRequest)
            $this->providerRepInRequest->load([]);
    }

    public function addDeferredServices(array $services)
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    public function runningInConsole()
    {
        if (defined('HONEST_IN_CONSOLE')) {
            return HONEST_IN_CONSOLE;
        } else {
            return parent::runningInConsole();
        }
    }


}
