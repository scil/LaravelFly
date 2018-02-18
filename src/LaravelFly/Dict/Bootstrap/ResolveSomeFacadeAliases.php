<?php

namespace LaravelFly\Dict\Bootstrap;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;
use LaravelFly\Dict\Application;

class ResolveSomeFacadeAliases
{
    protected $black = [
        'Request',
        // ReflectionMethod invoke leads to black hole
        'Schema',
        //todo why 'url' has made? when? \Illuminate\Routing\RoutingServiceProvider
        'URL',
    ];

    public function bootstrap(Application $app)
    {

        $all = array_keys(array_merge(
            $app->make('config')->get('app.aliases'),
            $app->make(PackageManifest::class)->aliases()));

        foreach ($all as $staticClass) {
            if (in_array($staticClass, $this->black)) {
                continue;
            }

            try {
                $method = new \ReflectionMethod($staticClass, 'getFacadeAccessor');
            } catch (\ReflectionException $e) {
                // Illuminate\Database\Eloquent\Model has no method getFacadeAccessor
                // todo: user model like User, Quote ?
                continue;
            }

            $method->setAccessible(true);
            $facadeAccessor = $method->invoke(null);

            if (is_object($facadeAccessor)) {
                // such as \Illuminate\Support\Facades\Blade
                continue;
            }

            if ($app->instanceResolvedOnWorker($facadeAccessor)) {
                $staticClass::getFacadeRoot();
            }
        }

    }
}
