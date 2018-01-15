<?php

namespace LaravelFly;


trait ApplicationTrait
{
    public function getCachedServicesPathInRequest()
    {
        return $this->bootstrapPath() . '/cache/laravelfly_services_in_request.json';
    }

    /**
     * replace \Illuminate\Foundation\Bootstrap\BootProviders::class,
     * code from \Illuminate\Foundation\Application::boot
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        /**  array_walk
         * If when a provider booting, it reg some other providers,
         * then the new providers added to $this->serviceProviders
         * then array_walk will loop the new ones and boot them. // pingpong/modules 2.0 use this feature
         */
        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

}