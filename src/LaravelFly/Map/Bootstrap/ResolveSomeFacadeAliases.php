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
     * @param $app
     * @return string
     */
    public function getCachedAliasesPath($app)
    {
        return $app->bootstrapPath() . '/cache/laravelfly_aliases.php';
    }

    protected function getAliases($app)
    {
        $cacheFile = $this->getCachedAliasesPath($app);

        if (is_file($cacheFile)) {
            $cacheTime = filemtime($cacheFile);
            if ($cacheTime >= filemtime($app->configPath('app.php')) && $cacheTime >= filemtime($app->basePath('composer.lock'))) {
                return require $cacheFile;
            }
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

        file_put_contents($cacheFile, '<?php return ' . var_export($aliases, true) . ';' . PHP_EOL);

        return $aliases;

    }

    public function bootstrap(Application $app)
    {
        foreach ($this->getAliases($app) as $staticClass) {
            $staticClass::getFacadeRoot();
        }
    }
}
