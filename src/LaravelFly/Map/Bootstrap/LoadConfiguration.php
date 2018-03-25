<?php
/**
 * User: scil
 * Date: 2018/3/25
 * Time: 22:41
 */

namespace LaravelFly\Map\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Finder\Finder;

/**
 * Class LoadConfiguration
 *
 * It can load laravelfly config when config cache is used ( php artisan config:cache )
 *
 * @package LaravelFly\Map\Bootstrap
 */
class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{

    /**
     * @param \LaravelFly\Map\Application $app
     */
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        $config = $app->make('config');

        if (empty($config['laravelfly'])) {

            $files = $this->getFlyConfigurationFiles($app);
            foreach ($files as $key => $path) {
                $config->set($key, require $path);
            }

        }
    }

    /**
     *
     * from: getConfigurationFiles()
     *
     * @param Application $app
     * @return array
     */
    protected function getFlyConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        foreach (Finder::create()->files()->name('laravelfly.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }
}