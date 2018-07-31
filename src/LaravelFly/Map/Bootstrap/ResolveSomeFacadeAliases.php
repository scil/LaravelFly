<?php

namespace LaravelFly\Map\Bootstrap;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;
use LaravelFly\Map\Application;

class ResolveSomeFacadeAliases
{
    protected $black = [
        'Request',
        // ReflectionMethod invoke leads to black hole
        'Schema',
        //todo why 'url' has made? when? \Illuminate\Routing\RoutingServiceProvider
        'URL',
    ];

    /**
     * use cache for aliases
     *
     * it's handy to debug and a little faster. in a test using microtime(true): 0.06 vs 0.26
     *
     * @param \LaravelFly\Map\Application $app
     * @return array
     */
    protected function getAliases(Application $app): array
    {
        $cacheFile = $app->bootstrapPath('/cache/laravelfly_aliases.php');

        $configCacheAlways = $app->make('config')['laravelfly.config_cache_always'];

        if ($configCacheAlways && is_file($cacheFile)) {
            /**
             * not needed to check filemtime
             * @see:\LaravelFly\Map\Bootstrap\LoadConfiguration @unlink( $app->bootstrapPath('/cache/laravelfly_aliases.php'));
             * mtime of this file is always > '/cache/laravelfly_config.php' whose mtime > app.php or composer.lock
             */
            return require $cacheFile;
        }


        $aliases = [];

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
                // todo
                continue;
            }

            if ($app->instanceResolvedOnWorker($facadeAccessor)) {
                $aliases[] = $staticClass;
            }
        }

        if($configCacheAlways){

            file_put_contents($cacheFile, '<?php return ' . var_export($aliases, true) . ';' . PHP_EOL);

            \LaravelFly\Fly::getServer()->echo("cache created: $cacheFile");

        }

        return $aliases;

    }

    public function bootstrap(Application $app)
    {
        foreach ($this->getAliases($app) as $staticClass) {
            $staticClass::getFacadeRoot();
        }
    }
}
