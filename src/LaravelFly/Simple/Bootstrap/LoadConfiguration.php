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

        if (file_exists($cacheFile = $app->bootstrapPath($this->service_cache_file))) {
            echo \LaravelFly\Fly::getInstance()->getServer()->colorize(
                "[NOTE] include: $cacheFile
                if any configs or composer.json changed, please re-run 'php artisan config:cache'\n",
                'NOTE'
            );
            list($psAcross, $psInRequest) = require $cacheFile;
        } else {

            $appConfig = $app->make('config');

            if (!$appConfig['laravelfly']) {
                die("no file config/laravelfly.php, please run `php artisan vendor:publish --tag=fly-app`");
            }

            $psInRequest = $appConfig['laravelfly.providers_in_request'] ?: [];

            $psAcross = array_diff(
                array_merge($appConfig['app.providers'], $app->make(PackageManifest::class)->providers()),
                $psInRequest,
                $appConfig['laravelfly.providers_ignore'] ?: []
            );

            file_put_contents($cacheFile, '<?php return ' .
                var_export([$psAcross, $psInRequest,], true) .
                ';' . PHP_EOL);

            echo "[INFO] cache created: $cacheFile\n";

            // ensure aliases cache file not outdated
            @unlink($app->bootstrapPath('/cache/laravelfly_aliases.php'));

        }


        // 'app.providers' only providers across
        $appConfig['app.providers'] = $psAcross;

        $app->makeManifestForProvidersInRequest($psInRequest);


    }

}