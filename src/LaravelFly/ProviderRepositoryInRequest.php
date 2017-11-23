<?php

namespace LaravelFly;


class ProviderRepositoryInRequest extends \Illuminate\Foundation\ProviderRepository
{
    public $manifest;

    /**
     * Override
     */
    public function makeManifest(array $providers)
    {
        $manifest = $this->loadManifest();

        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers);
        }

        $this->manifest = $manifest;
    }

    /**
     * Override
     * @param  array  [] it's useless, there should be an argument when overridde
     */
    public function load(array $providers)
    {

        $manifest = $this->manifest;

        if (isset($manifest['when'])) {
            foreach ($manifest['when'] as $provider => $events) {
                $this->registerLoadEvents($provider, $events);
            }
        }

        if (isset($manifest['eager'])) {
            foreach ($manifest['eager'] as $provider) {
                $this->app->register($provider);
            }
        }

        if ($manifest['deferred']) {
            $this->app->addDeferredServices($manifest['deferred']);
        }
    }


}
