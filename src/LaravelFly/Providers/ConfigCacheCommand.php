<?php


namespace LaravelFly\Providers;

class ConfigCacheCommand extends \Illuminate\Foundation\Console\ConfigCacheCommand
{

    protected $signature = 'config:cache 
                        {serverConfigFile? : (optional) The config file of LaravelFly server}';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:clear');

        $config = $this->getFreshConfiguration();

        $serverConfigFile = $this->argument('serverConfigFile') ?: $this->laravel->basePath() . '/fly.conf.php';

        if (!is_file($serverConfigFile))
            $this->error("LaravelFly server conf file not exists: $serverConfigFile");

        // var $basePath is useful, can be used in $serverConfigFile
        $basePath = $this->getApplication()->getLaravel()->basePath();
        include $serverConfigFile;
        $this->info("[LaravelFly] server conf file $serverConfigFile included.");

        $allConfig = $this->getFreshConfiguration();

        $this->files->put(
            $this->laravel->getCachedConfigPath(), '<?php return defined("LARAVELFLY_MODE")? ' .
            var_export($allConfig, true) .
            ':' .
            var_export($config, true) .
            ';' . PHP_EOL
        );

        $this->info('Configuration cached successfully!');
    }

}
