<?php

namespace LaravelFly\Simple\Bootstrap;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{

    var $service_cache_file = 'cache/laravelfly_ps_simple.php';

    /**
     * @param \LaravelFly\Map\Application $app
     */
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        $appConfig = $app->make('config');

        $configCacheAlways = $appConfig['laravelfly.config_cache_always'];

        if ($configCacheAlways && file_exists($cacheFile = $app->bootstrapPath($this->service_cache_file))) {
            \LaravelFly\Fly::getServer()->echo(
                "include: $cacheFile
                if any configs or composer.json changed, please re-run 'php artisan config:clear'",
                'NOTE',true
            );
            list('psAcross' => $psAcross,
                'psInRequest' => $psInRequest) = require $cacheFile;
        } else {

            if (!$appConfig['laravelfly']) {
                die("no file config/laravelfly.php, please run `php artisan vendor:publish --tag=fly-app`");
            }

            $psInRequest = $appConfig['laravelfly.providers_in_request'] ?: [];

            $psAcross = array_diff(
                array_merge($appConfig['app.providers'], $app->make(PackageManifest::class)->providers()),
                $psInRequest,
                $appConfig['laravelfly.providers_ignore'] ?: []
            );

            if($configCacheAlways){

                file_put_contents($cacheFile, '<?php return ' .
                    var_export([
                        'psAcross' => $psAcross,
                        'psInRequest' => $psInRequest
                    ], true) .
                    ';' . PHP_EOL);

                \LaravelFly\Fly::getServer()->echo("cache created: $cacheFile",'INFO');

            }

        }


        // 'app.providers' only providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);


    }

}