<?php

namespace LaravelFly\Map\Bootstrap;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{

    var $service_cache_file = 'cache/laravelfly_ps_map.php';

    /**
     * @param \LaravelFly\Map\Application $app
     */
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        if (file_exists($cacheFile = $app->bootstrapPath($this->service_cache_file))) {
            echo \LaravelFly\Fly::getServer()->colorize(
                "[NOTE] include: $cacheFile
                if any configs or composer.json changed, please re-run 'php artisan config:clear'\n",
                'NOTE'
            );
            list($CFServices, $psOnWork, $psAcross, $psInRequest) = require $cacheFile;
        } else {

            $appConfig = $app->make('config');

            if(!$appConfig['laravelfly']){
                die("no file config/laravelfly.php, please run `php artisan vendor:publish --tag=fly-app`");
            }

            $psInRequest = $appConfig['laravelfly.providers_in_request']?:[];

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
                $appConfig['laravelfly.providers_ignore']?:[]
            );

            file_put_contents($cacheFile, '<?php return ' .
                var_export([$CFServices, $psOnWork, $psAcross, $psInRequest,], true) .
                ';' . PHP_EOL);

            echo "[INFO] cache created: $cacheFile\n";

            // ensure aliases cache file not outdated
            @unlink($app->bootstrapPath('/cache/laravelfly_aliases.php'));

        }


        // 'app.providers' only providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);

        $app->setProvidersToBootOnWorker($psOnWork);

        $app->setCFServices($CFServices);

    }

}