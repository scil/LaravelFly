<?php

namespace LaravelFly\Greedy\Bootstrap;

use LaravelFly\Greedy\Application;

class FindViewFiles
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($views = $app->make('config')->get('laravelfly.views_to_find_in_worker')) {
            $finder = $app->make('view')->getFinder();

            try {
                foreach ($views as $view) {
                    $finder->find($view);
                }
            } catch (\Exception $e) {
                exit(" view $view not found, please check your config 'laravelfly.views_to_find_in_worker'");
            }
        }

    }
}
