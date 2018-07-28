<?php

namespace LaravelFly\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

class RouteServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function register()
    {

    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $config = $this->app['config']->get('laravelfly.route');

        $routeConfig = [
            'namespace' => 'LaravelFly\Http\Controllers',
            'prefix' => !empty($config['prefix']) ? $config['prefix'] : 'laravel-fly',
//            'domain' => !empty($config['domain']) ? $config['domain'] : '',
//            'middleware' => [DebugbarEnabled::class],
        ];

        $this->getRouter()->group($routeConfig, function ($router) {
            $router->get('info', [
                'uses' => 'InfoController@index',
                'as' => 'laravelfly.info',
            ]);

            $router->get('clockwork/{id}', [
                'uses' => 'OpenHandlerController@clockwork',
                'as' => 'ugbar.clockwork',
            ]);

            $router->get('assets/stylesheets', [
                'uses' => 'AssetController@css',
                'as' => 'ugbar.assets.css',
            ]);

            $router->get('assets/javascript', [
                'uses' => 'AssetController@js',
                'as' => 'ugbar.assets.js',
            ]);

            $router->delete('cache/{key}/{tags?}', [
                'uses' => 'CacheController@delete',
                'as' => 'ugbar.cache.delete',
            ]);
        });

    }

    /**
     * Get the active router.
     *
     * @return Router
     */
    protected function getRouter()
    {
        return $this->app['router'];
    }

}
