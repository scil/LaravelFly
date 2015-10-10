<?php

namespace LaravelFly;


class ProviderRepository extends \Illuminate\Foundation\ProviderRepository
{
    /**
     * Override
     */
    public function load(array $providers)
    {
        $manifest = $this->loadManifest();

        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers);
        }

        if (isset($manifest['when'])) {
            foreach ($manifest['when'] as $provider => $events) {
                $this->registerLoadEvents($provider, $events);
            }
        }

        foreach ($manifest['eager'] as $provider) {
            $this->app->register($this->createProvider($provider));
        }

        // laravelfly
        // this function sould run more than one time
        // $this->app->setDeferredServices($manifest['deferred']);
        if ($manifest['deferred']) {
            $this->app->addDeferredServices($manifest['deferred']);
        }
    }


}
