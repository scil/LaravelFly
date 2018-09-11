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
        $config = $this->app['config']->get('laravelfly.web',[]);

        if(empty($config['enable'])) return;

        $prefix = $config['prefix'] ?? 'laravel-fly';
        \View::share('LARAVEL_FLY_PREFIX',$prefix);

        $this->server = $this->app->getServer();
        $swoole = $this->swoole = $this->server->getSwooleServer();
        \View::share('WORKER_PID' , $swoole->worker_pid);
        \View::share('WORKER_ID' , $swoole->worker_id);

        \View::share('INFO_ITEMS',['info','header','eventListeners','routes']);

        $routeConfig = [
            'namespace' => 'LaravelFly\FrontEnd\Controllers',
            'prefix' => $prefix,
//            'domain' => !empty($config['domain']) ? $config['domain'] : '',
//            'middleware' => [DebugbarEnabled::class],

        ];

        $this->loadViewsFrom(__DIR__.'/../FrontEnd/views', 'laravel-fly');

        $this->getRouter()->group($routeConfig, function ($router) {
            $router->get('info/{sub?}', [
                'uses' => 'InfoController@index',
                'as' => 'laravelfly.info',
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
