<?php

namespace LaravelFly\Simple\Bootstrap;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{


    /**
     * @param \LaravelFly\Map\Application $app
     */
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        if (file_exists($cacheFile = $app->bootstrapPath('cache/laravelfly_config_simple.php')) &&
            ($mtime = filemtime($cacheFile)) > filemtime($app->getServer()->getConfig('conf')) &&
            $mtime > filemtime($app->configPath('laravelfly.php')) &&
            $mtime > filemtime($app->configPath('app.php')) &&
            $mtime > filemtime($app->basePath('composer.lock')) &&   // because PackageManifest::class
            (
            file_exists($envFlyFile = $app->configPath($app['env'] . '/laravelfly.php')) ?
                $mtime > filemtime($envFlyFile) : true)
        ) {
            list($psAcross, $psInRequest) = require $cacheFile;
            echo "[INFO] include: $cacheFile\n";
        } else {

            $appConfig = $app->make('config');

            $psInRequest = $appConfig['laravelfly.providers_in_request'];

            $psAcross = array_diff(
                array_merge($appConfig['app.providers'], $app->make(PackageManifest::class)->providers()),
                $psInRequest,
                $appConfig['laravelfly.providers_ignore']
            );

            file_put_contents($cacheFile, '<?php return ' .
                var_export([$psAcross, $psInRequest,], true) .
                ';' . PHP_EOL);

            echo "[INFO] cache created: $cacheFile\n";

            // ensure aliases cache file not outdated
            @unlink($app->bootstrapPath('/cache/laravelfly_aliases.php'));

            if (file_exists($cached = $app->getCachedConfigPath())) {
                echo "[NOTE] include config cache $cached, 
               if it's outdated please re-run 'php artisan config:cache'\n";
            }
        }


        // 'app.providers' only providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);


    }

}