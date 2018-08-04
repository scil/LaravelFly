<?php

namespace LaravelFly\Backup;


class ProviderRepositoryInRequest extends \Illuminate\Foundation\ProviderRepository
{

    /**
     * store service providers in request.
     *
     * This var is added, as app['config']['laravelfly.providers_in_request']
     * are made for manifest on work, but loaded in request.
     *
     * @var array|null
     */
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

        if(!($manifest = $this->manifest)) return;

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
