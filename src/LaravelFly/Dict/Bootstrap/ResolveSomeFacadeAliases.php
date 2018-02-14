<?php

namespace LaravelFly\Dict\Bootstrap;

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

        $aliasAndInstance = [];
        foreach (array_keys($app->make('config')->get('app.aliases')) as $staticClass) {
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
            $alias = $method->invoke(null);
            if (is_object($alias)) {
                // such as \Illuminate\Support\Facades\Blade
                continue;
            }

            if ($app->instanceResolvedOnWorker($alias)) {
                $aliasAndInstance[$alias] = $app->getInstanceOnWorker($alias);
            }
        }
        Facade::initOnWorker($aliasAndInstance);

    }
}
