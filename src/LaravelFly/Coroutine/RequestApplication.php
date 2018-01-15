<?php

namespace LaravelFly\Coroutine;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\ProviderRepository;

class RequestApplication extends \LaravelFly\Coroutine\BaseApplication
{
    private $providers;

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


}