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
                'cloneServices' => $cloneServices,
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
            $cloneServices = [];
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
                    $providerConfig = [];
                } elseif (!is_array($providerConfig)) {
                    $providerConfig = [];
                }

                if (!empty($providerConfig['_replaced_by'])) {
                    $providersReplaced[] = $provider;
                    $provider = $providerConfig['_replaced_by'];
                    unset($providerConfig['_replaced_by']);
                }

                if (!class_exists($provider)) continue;

                $psOnWork[] = $provider;

                foreach ($providerConfig ?: $provider::coroutineFriendlyServices() as $serviceName => $serviceConfig) {
                    if (is_int($serviceName)) {
                        $serviceName = $serviceConfig;
                        $serviceConfig = true;
                    }

                    if ($serviceConfig) {

                        $CFServices[] = $serviceName;

                        if ($serviceConfig === 'clone') {
                            $cloneServices[] = $serviceName;
                        }

                    }
                }


            }

            //todo just for debug
//             $psOnWork = array_diff($psOnWork,$psIgnore);

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
                    'NOTE');
            }


            $allClone = implode(", ", array_merge(LARAVELFLY_SERVICES['routes'] ? ['url'] : ['url', 'routes'], $cloneServices));
            echo \LaravelFly\Fly::getServer()->colorize(
                "[NOTE] services to be cloned in each request: [ $allClone ]. An object in your code should update references if it is made before any requets and has a relation with any of these services, see config('laravel.update_for_clone').\n",
                'NOTE'
            );

            if ($configCacheAlways) {

                file_put_contents($cacheFile, '<?php return ' .
                    var_export([
                        'CFServices' => $CFServices,
                        'cloneServices' => $cloneServices,
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


        $update = [];

        foreach ($appConfig['laravelfly.update_for_clone'] ?: [] as $item) {
            if ($item && $item['this'] && $item['closure']) {
                $update[] = $item;
            }
        }

        $app->setCloneServices($cloneServices, $update);

    }

}