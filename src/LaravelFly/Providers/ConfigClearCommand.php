<?php


namespace LaravelFly\Providers;

class ConfigClearCommand extends \Illuminate\Foundation\Console\ConfigClearCommand
{

    protected $signature = 'config:clear';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        @unlink($this->laravel->bootstrapPath('cache/laravelfly_ps_simple.php'));
        @unlink($this->laravel->bootstrapPath('cache/laravelfly_ps_map.php'));
        @unlink($this->laravel->bootstrapPath('cache/laravelfly_aliases.php'));
        $this->info("LaravelFly ps configuration cache cleard!");

    }

}
