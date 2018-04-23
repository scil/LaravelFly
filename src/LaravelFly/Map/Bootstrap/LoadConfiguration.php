<?php

namespace LaravelFly\Map\Bootstrap;

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

        if (file_exists($cacheFile = $app->bootstrapPath('/cache/laravelfly_config.php')) &&
            ($mtime = filemtime($cacheFile)) > filemtime($app->getServer()->getConfig('conf')) &&
            $mtime > filemtime($app->configPath('laravelfly.php')) &&
            $mtime > filemtime($app->configPath('app.php')) &&
            $mtime > filemtime($app->basePath('composer.lock')) &&   // because PackageManifest::class
            (
            file_exists($envFlyFile = $app->configPath($app['env'] . '/laravelfly.php')) ?
                $mtime > filemtime($envFlyFile) : true)
        ) {
            list($CFServices, $psOnWork, $psAcross, $psInRequest) = require $cacheFile;

        } else {

            $appConfig = $app->make('config');

            $psInRequest = $appConfig['laravelfly.providers_in_request'];

            $CFServices = [];
            $providersReplaced = [];
            $psOnWork = [];
            foreach ($app['config']['laravelfly.providers_on_worker'] as $provider => $providerConfig) {

                if ($providerConfig === false || $providerConfig === null) continue;

                if (!class_exists($provider)) continue;

                $psOnWork[] = $provider;

                if (!is_array($providerConfig)) continue;

                if (isset($providerConfig['_replace'])) {
                    $providersReplaced[] = $providerConfig['_replace'];
                    unset($providerConfig['_replace']);
                }

                /** @var string[] $officalCFServices */
                /** @var \Illuminate\Support\ServiceProvider $provider */
                $officalCFServices = $provider::coroutineFriendlyServices();
                // $officalCFServices is base
                $curCFServices = $officalCFServices;

                foreach ($providerConfig as $CFS_name => $config) {
                    // true $config only works when empty $officalCFServices
                    if ($config === true && !$officalCFServices) {
                        $curCFServices[] = $CFS_name;
                    } elseif ($config === false && in_array($CFS_name, $officalCFServices)) {
                        $curCFServices = array_diff($curCFServices, [$CFS_name]);
                    }
                }


                if ($curCFServices) {
                    $CFServices = array_unique(array_merge($curCFServices, $CFServices));
                }
            }

            $psAcross = array_diff(
                array_merge($appConfig['app.providers'], $app->make(PackageManifest::class)->providers()),
                $providersReplaced,
                $psOnWork,
                $psInRequest,
                $appConfig['laravelfly.providers_ignore']
            );

            file_put_contents($cacheFile, '<?php return ' .
                var_export([$CFServices, $psOnWork, $psAcross, $psInRequest,], true) .
                ';' . PHP_EOL);

            // ensure aliases cache file not outdated
            @unlink($app->bootstrapPath('/cache/laravelfly_aliases.php'));

            if (file_exists($cached = $app->getCachedConfigPath())) {
               echo "[NOTICE] config cache $cached used, 
               if it's outdated please re-run 'php artisan config:cache'\n";
            }
        }


        // 'app.providers' only providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);

        $app->setProvidersToBootOnWorker($psOnWork);

        $app->setCFServices($CFServices);

    }

}