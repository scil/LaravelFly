<?php
/**
 * no more PackageManifest again
 */

namespace LaravelFly\ApplicationTrait;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use LaravelFly\Backup\ProviderRepository;
use Illuminate\Support\Str;

trait NoMorePackageManifest
{
    public function registerConfiguredProviders()
    {
        $providers = Collection::make($this->config['app.providers'])
            ->partition(function ($provider) {
                return Str::startsWith($provider, 'Illuminate\\');
            });

        // no more, they have loaded by LaravelFly\Backup\Bootstrap\LoadConfiguration
        // or LaravelFly\Map\Bootstrap\LoadConfiguration
        // $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());
    }
}