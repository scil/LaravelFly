<?php

if (!defined('LARAVELFLY_MODE')) return [];

$IN_PRODUCTION = env('APP_ENV') === 'production'
    || env('APP_ENV') === 'product'
    // for test
    || ($GLOBALS['IN_PRODUCTION'] ?? false);

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

return [

    /**
     * show server info at url /laravel-fly/info by default
     *
     * It's better to view json response in Firefox, instead of Chrome or IE
     */

    'web' => [
        'enable' => true,
        'prefix' => 'laravel-fly',
    ],

    /**
     * by default, all models can be booted  on work.
     *
     * But if a model add a third-party trait, you should check if the trait can be booted on work.
     * [Laravel Tip: Bootable Model Traits ](https://tighten.co/blog/laravel-tip-bootable-model-traits/)
     *
     * when a trait can boot on work?
     * 1. if it has static prop, the prop should
     *      always be same when coroutine used,
     *      or does not harm the next request when coroutines not used.
     * 2. if it has a reference prop, the prop should be a WORKER SERVICE or WORKER OBJECT
     */
    'models_booted_on_work' => [
        'App\User',
        // 'App\Article',
    ],

    /**
     * If use cache file for config/laravel.php always.
     *
     * If true, Laravelfly will always use cache file
     *  laravelfly_ps_map.php
     * or
     *  laravelfly_ps_simple.php
     * and laravelfly_aliases.php
     * under bootstrap/cache/ when the files exist. If not exist, Laravelfly will create them.
     *
     * It's better to set it to false in dev env , set true and run `php artisan config:clear` before starting LaravelFly in production env
     */
    'config_cache_always' => $IN_PRODUCTION,
    // 'config_cache_always' => true,

    /**
     * For each worker, if a view file is compiled max one time.
     *
     * If true, Laravel not know a view file changed until the swoole workers restart.
     * It's good for production env.
     */
    'view_compile_1' => $IN_PRODUCTION && LARAVELFLY_SERVICES['view.finder'],

    /**
     * useless providers.
     *
     * These providers are useless if they are not enabled in
     * config('laravelfly.providers_on_worker') or
     * config('laravelfly.providers_in_request')
     *
     * There providers will be removed from config('app.providers')
     */
    'providers_ignore' => array_merge([

        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Laravel\Tinker\TinkerServiceProvider::class,
        Fideloper\Proxy\TrustedProxyServiceProvider::class,
        LaravelFly\Providers\ServiceProvider::class,
        Hhxsv5\LaravelS\Illuminate\LaravelSServiceProvider::class,
        'Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider',

    ], !!LARAVELFLY_SERVICES['broadcast'] ? [] : [
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Broadcasting\BroadcastManager::class,
        Illuminate\Contracts\Broadcasting\Broadcaster::class,
        App\Providers\BroadcastServiceProvider::class
    ], $IN_PRODUCTION ? [
        'Barryvdh\\Debugbar\\ServiceProvider',
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
    ] : []
    ),

    /**
     * Providers to reg and boot in each request.
     *
     * There providers will be removed from app('config')['app.providers'] on worker, before any requests
     */
    'providers_in_request' => [
    ],


    /**
     * providers to reg and boot on worker, before any request.
     *
     * you can also supply singleton services to made on worker
     * only singleton services are useful and valid here.
     * a singeton service is like this:
     *     *   $this->app->singleton('hash', function ($app) { ... });
     * or
     *     *   $this->app->instance('hash', new Hash());
     *
     * There are two types of singleton services:
     *   - COROUTINE-FRIENDLY SERVICE:  https://github.com/scil/LaravelFly/wiki/WORKER-OBJECT
     *   - CLONE SERVICE : any service can be a CLONE SERVICE, but take care of
     * Stale Reference https://github.com/scil/LaravelFly/wiki/clone-and-Stale-Reference
     *
     * If a service is not a COROUTINE-FRIENDLY SERVICE, neither a CLONE SERVICE that Stale Reference fixed,
     * it should not be list here.
     *
     *
     * formats:
     *      proverder,                   // this provider will be booted on worker
     *      proverder2=> true,           // this provider will be booted on worker
     *      proverder1=> [],             // this provider will be booted on worker
     *      proverder3=> [
     *        '_replaced_by' => 'provider1',       // the provider1 will replace provider3 and provider3 will be deleted from app['config']['app.providers']
     *
     *        'singleton_service_1',          //  services will be made on worker
     *
     *        'singleton_service_2' => true,  //  service will be made on worker
     *
     *        'singleton_service_3' => false, //  service will not be made on worker,
     *
     *        'singleton_service_3' => 'clone', //  service will be a CLONE SERVICE, so there would be more than one instances in a worker process.
     *                                          //  To avoid Stale Reference it's necessary to update relations if some objects have ref to the service,
     *                                          //  see config 'laravelfly.update_on_request'
     *      ],
     *
     *      proverder4=> false,           // this provider will not be booted on worker
     *      proverder5=> null,           // this provider will not be booted on worker too.
     *      proverder6=> 'across',           // this provider will not be booted on worker too.
     *      proverder7=> 'request',           // just like config('laravelfly.providers_in_request')
     *      proverder8=> 'ignore',           // just like config('laravelfly.providers_ignore')
     */
    'providers_on_worker' => [

        // this is not in config('app.providers') but registered in Application:;registerBaseServiceProviders
        Illuminate\Log\LogServiceProvider::class => [
            'log' => true,
        ],

        // this is not in config('app.providers') but registered in Application:;registerBaseServiceProviders
        Illuminate\Routing\RoutingServiceProvider::class => [
            'router' => true,
            'url' => true,
            // todo
            'redirect' => false,
        ],

        Illuminate\Auth\AuthServiceProvider::class => [
            '_replaced_by' => LaravelFly\Map\Illuminate\Auth\AuthServiceProvider::class,
            'auth',
            GateContract::class,
        ],

        Illuminate\Broadcasting\BroadcastServiceProvider::class =>
            !!LARAVELFLY_SERVICES['broadcast'] ? [
                Illuminate\Broadcasting\BroadcastManager::class,
                Illuminate\Contracts\Broadcasting\Broadcaster::class,
            ] : 'ignore',


        /* depends */
        // comment it if Queues not used
        Illuminate\Bus\BusServiceProvider::class => [
            \Illuminate\Bus\Dispatcher::class,
        ],

        Illuminate\Cache\CacheServiceProvider::class => [
            'cache' => true,
            'cache.store' => true,

            /* depends */
            // if memcached is used, enable it
            // 'memcached.connector' => true,

        ],

        Illuminate\Cookie\CookieServiceProvider::class => [
            '_replaced_by' => LaravelFly\Map\Illuminate\Cookie\CookieServiceProvider::class,
            'cookie'
        ],

        Illuminate\Database\DatabaseServiceProvider::class => [
            '_replaced_by' =>
                LARAVELFLY_COROUTINE ? LaravelFly\Map\Illuminate\Database\DatabaseServiceProvider::class : false,
            'db.factory',
            'db'
        ],

        Illuminate\Encryption\EncryptionServiceProvider::class => [
            'encrypter' => true,
        ],

        Illuminate\Filesystem\FilesystemServiceProvider::class => [

            'files' => true,

            'filesystem.disk' => true,

            /* depends */
            // set this service to be 'use' if you use it.
            'filesystem.cloud' => false,
        ],

        /* This reg FormRequestServiceProvider, whose boot is related to request */
        Illuminate\Foundation\Providers\FoundationServiceProvider::class => 'across',

        Illuminate\Hashing\HashServiceProvider::class => [
            // 'hash' => !empty(LARAVELFLY_SERVICES['hash']) ? true : 'clone',
            'hash' => true, // no need to clone it when empty(LARAVELFLY_SERVICES['hash'], as changed props not belongs to 'hash', but to drivers
            'hash.driver',
        ],

        Illuminate\Mail\MailServiceProvider::class => [],

        Illuminate\Notifications\NotificationServiceProvider::class => 'across',

        Illuminate\Pagination\PaginationServiceProvider::class => [],

        Illuminate\Pipeline\PipelineServiceProvider::class => [],

        Illuminate\Queue\QueueServiceProvider::class => [],

        Illuminate\Redis\RedisServiceProvider::class =>
            !!LARAVELFLY_SERVICES['redis'] ? [
                '_replaced_by' =>
                    LARAVELFLY_COROUTINE ? LaravelFly\Map\Illuminate\Redis\RedisServiceProvider::class : false,
                'redis' => true,
            ] : 'ignore',

        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class => [],

        Illuminate\Session\SessionServiceProvider::class => [
            '_replaced_by' => LaravelFly\Map\Illuminate\Session\SessionServiceProvider::class,
            'session',
            'session.store',
            \Illuminate\Session\Middleware\StartSession::class
        ],

        Illuminate\Translation\TranslationServiceProvider::class =>
        // todo
//            !!LARAVELFLY_SERVICES['translator'] || !!LARAVELFLY_SERVICES['validator'] ?
            [
                '_replaced_by' => LaravelFly\Map\Illuminate\Translation\TranslationServiceProvider::class,
                'translation.loader' => true,
                'translator' => true,
            ]
//                : 'ignore'
        ,

        Illuminate\Validation\ValidationServiceProvider::class =>
            !!LARAVELFLY_SERVICES['validator'] ? [
                'validator' => true,
                'validation.presence' => true,
            ] : 'ignore',

        Illuminate\View\ViewServiceProvider::class => [
            '_replaced_by' => \LaravelFly\Map\Illuminate\View\ViewServiceProvider::class,
            'view', 'view.engine.resolver', 'blade.compiler'
        ],


        /*
         * LaravelFly
         *
         */

        /**
         * /laravel-fly/info
         */
        LaravelFly\Providers\RouteServiceProvider::class => [],


        /*
         * Application Service Providers...
         */


        /* depends */
        /**
         * if it's register and boot need executing in each request, set to 'request' or move it to 'providers_in_request'
         * if it's boot can execute on worker (before any requests), set to true or [].
         */
        App\Providers\AppServiceProvider::class => 'across',

        /* depends */
        /**
         * if some executions always be same in each request,
         * suggest to create a new AppServiceProvider whoes reg and boot are both executed on worker.
         */
        // App\Providers\AppWorker_ServiceProvider::class => [],

        /* depends */
        App\Providers\AuthServiceProvider::class => 'across',

        App\Providers\BroadcastServiceProvider::class => !!LARAVELFLY_SERVICES['broadcast'] ? [] : 'ignore',

        /* depends */
        /**
         * if it's register and boot need executing in each request, set to 'request' or move it to 'providers_in_request'
         * if it's events are always same in different request, set to true or [].
         */
        App\Providers\EventServiceProvider::class => 'across',

        /* depends */
        /**
         * set to 'across' if routes in routes/web.php or routes/api.php not always same,
         * like this in mcamara/laravel-localization
         *      // LaravelLocalization::setLocale uses \Request::segment(1) which changes in each request
         *      Route::group(['prefix' => LaravelLocalization::setLocale()], function() { ... } );
         * so App\Providers\RouteServiceProvider should boot in each request.
         *
         * Try best not to set it to 'across', then routes cache useful.
         * (this cache refers to php code in computer memory, not cache file produced by `artisan route:cache`)
         *
         * background: its boot loads files routes/web.php and routes/api.php.
         * see:
         *      Illuminate\Foundation\Support\Providers\RouteServiceProvider::boot()
         * and
         *      App\Providers\RouteServiceProvider::map
         *
         */
        // App\Providers\RouteServiceProvider::class => 'across',
        App\Providers\RouteServiceProvider::class => [],

        /*
         * Third Party
         */

        // Collision is an error handler framework for console/command-line PHP applications such as laravelfly
        NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class => [
            Illuminate\Contracts\Debug\ExceptionHandler::class => true,
        ],


    ],


    /**
     * handle relations about cloned objects to avoid Stale Reference. For Mode Map
     *
     * clone and closure run in each request.
     */
    'update_on_request' => [

        // for hash
        !empty(LARAVELFLY_SERVICES['hash']) ? false :
            [
                'this' => 'hash',
                'closure' => function () {
                    // $this here is app('hash'), the instance of HashManager
                    // by default, $name is bcrypt and then argon
                    foreach ($this->getDrivers() as $name => $drive) {
                        $this->drivers[$name] = clone $drive;
                        // debug_zval_dump($this->drivers[$name] );
                    }
                },
            ],

        // put one more updating item here
        [
            // 'this' => 'name',   // 'this' is optional, and userful when closure is accessing protected props
            // 'closure' => function () { },
        ]
    ],

    /**
     * these middlewares are instanced only one time.  For Mode Map.
     *
     * References bound in these middlewars are COROUTINE-FRIENDLY services.
     */
    'singleton_route_middlewares' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class, // hacked by LaravelFly\Map\Illuminate\Session\StartSession
        //todo
//        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,

        //todo
//        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        //todo
//        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        //todo
//        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        //todo
//        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ],

    // only helpful when LARAVELFLY_SERVICES['kernel']===true
    'singleton_middlewares' => [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ],

    'swoole-job' => [
        'delay' => 0,
        'memory' => 128,
        'timeout' => 60,
        'tries' => 0,
    ],

];

