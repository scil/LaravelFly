<?php

namespace LaravelFly\Simple;


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

        // this if is necessary
        if (isset($manifest['when'])) {
            foreach ($manifest['when'] as $provider => $events) {
                $this->registerLoadEvents($provider, $events);
            }
        }

        foreach ($manifest['eager'] as $provider) {
            $this->app->register($provider);
        }

        // this if is necessary
        if ($manifest['deferred']) {
            $this->app->addDeferredServices($manifest['deferred']);
        }
    }


}
