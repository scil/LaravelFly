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

        $appConfig = $app->make('config');

        $configCacheAlways = $appConfig['laravelfly.config_cache_always'];

        if ($configCacheAlways && file_exists($cacheFile = $app->bootstrapPath($this->service_cache_file))) {
            echo \LaravelFly\Fly::getServer()->colorize(
                "[NOTE] include: $cacheFile
                if any configs or composer.json changed, please re-run 'php artisan config:clear'\n",
                'NOTE'
            );
            list('CFServices' => $CFServices,
                'psOnWork' => $psOnWork,
                'psAcross' => $psAcross,
                'psInRequest' => $psInRequest) = require $cacheFile;
        } else {

            if (!$appConfig['laravelfly']) {
                die("no file config/laravelfly.php, please run `php artisan vendor:publish --tag=fly-app`");
            }

            $psInRequest = $appConfig['laravelfly.providers_in_request'] ?: [];
            $psIgnore = $appConfig['laravelfly.providers_ignore'] ?: [];

            $CFServices = [];
            $providersReplaced = [];
            $psOnWork = [];
            $psAcross = [];

            foreach ($app['config']['laravelfly.providers_on_worker'] as $provider => $providerConfig) {

                if ($providerConfig === 'across' || $providerConfig === false || $providerConfig === null) {
                    $psAcross[] = $provider;
                    continue;
                }

                if ($providerConfig === 'request') {
                    if (!in_array($provider, $psInRequest)) $psInRequest[] = $provider;
                    continue;
                }

                if ($providerConfig === 'ignore') {
                    if (!in_array($provider, $psIgnore)) $psIgnore[] = $provider;
                    continue;
                }

                if (is_int($provider)) {
                    $provider = $providerConfig;
                    $providerConfig = true;
                }

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

            $left = array_diff(
                array_merge($appConfig['app.providers'], $app->make(PackageManifest::class)->providers()),
                $providersReplaced,
                $psOnWork,
                $psAcross,
                $psInRequest,
                $psIgnore
            );

            if ($left) {
                $psAcross = array_merge($psAcross, $left);
                $left = implode("\n", $left);

                echo \LaravelFly\Fly::getServer()->colorize(
                    "[NOTE] These providers not listed in config('laravel.providers_on_worker'):
                    $left
                They will be registered before any request and be booted in each request\n",
                    'NOTE'
                );
            }

            if ($configCacheAlways) {

                file_put_contents($cacheFile, '<?php return ' .
                    var_export([
                        'CFServices' => $CFServices,
                        'psOnWork' => $psOnWork,
                        'psAcross' => $psAcross,
                        'psInRequest' => $psInRequest,
                        'psIgnore' => $psIgnore
                    ], true) .
                    ';' . PHP_EOL);

                echo "[INFO] cache created: $cacheFile\n";

            }

        }


        // 'app.providers' only hold providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);

        $app->setProvidersToBootOnWorker($psOnWork);

        $app->setCFServices($CFServices);

    }

}