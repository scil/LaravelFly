<?php


namespace LaravelFly\Providers;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

class ConfigCacheCommand extends \Illuminate\Foundation\Console\ConfigCacheCommand
{



    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:clear');

        $config = $this->getFreshConfiguration();

        $this->files->put(
            $this->laravel->getCachedConfigPath(), '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        $this->info('Configuration cached successfully!');
    }

}
