<?php

if (!defined('LARAVELFLY_MODE')) return [];

$IN_PRODUCTION = env('APP_ENV') === 'production' || env('APP_ENV') === 'product';

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

return [
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
     * For each worker, if a view file is compiled max one time. Only For Mode Map
     *
     * If true, Laravel not know a view file changed until the swoole workers restart.
     * It's good for production env.
     */
    'view_compile_1' => $IN_PRODUCTION && LARAVELFLY_SERVICES['view.finder'],

    /**
     * useless providers. For Mode Simple, Map
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
        'Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider',

    ], LARAVELFLY_SERVICES['broadcast'] ? [] : [
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Broadcasting\BroadcastManager::class,
        Illuminate\Contracts\Broadcasting\Broadcaster::class,
        App\Providers\BroadcastServiceProvider::class
    ]
    ),

    /**
     * Providers to reg and boot in each request.For Mode Simple, Map
     *
     * There providers will be removed from app('config')['app.providers'] on worker, before any requests
     */
    'providers_in_request' => [
    ],


    /**
     * providers to reg and boot on worker, before any request. only for Mode Map
     *
     * you can also supply singleton services to made on worker
     * only singleton services are useful and valid here.
     * a singeton service is like this:
     *     *   $this->app->singleton('hash', function ($app) { ... });
     *
     * There are two types of singleton services:
     *   - COROUTINE-FRIENDLY SERVICE
     *   - CLONE SERVICE
     *
     * A COROUTINE-FRIENDLY SERVICE that must satisfy folling conditions:
     *      1. singleton. A singleton service is made by by {@link Illuminate\Containe\Application::singleton()} or {@link Illuminate\Containe\Application::instance() }
     *      2. its vars will not changed in any requests
     *      3. if it has ref attibutes, like app['events'] has an attribubte `container`, the container must be also A COROUTINE-FRIENDLY SERVICE
     *
     * CLONE SERVICE: any service can be a CLONE SERVICE, but take care of Stale Reference ([Mode Map Safety Checklist](https://github.com/scil/LaravelFly/wiki/Mode-Map-Safety-Checklist))
     *
     * If a service is not a COROUTINE-FRIENDLY SERVICE, neither a CLONE SERVICE that Stale Reference handled,
     * it should not be made on worker.
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
     *        'singleton_service_3' => 'clone', //  service will be a CLONE SERVICE, so there are more than one instances,
     *                                          //  to avoid Stale Reference it's necessary to update relations if some objects have ref to the service,
     *                                          //  see config 'laravelfly.update_for_clone'
     *      ],
     *
     *      proverder4=> false,           // this provider will not be booted on worker
     *      proverder5=> null,           // this provider will not be booted on worker too.
     *      proverder6=> 'across',           // this provider will not be booted on worker too.
     *      proverder7=> 'request',           // just like config('laravelfly.providers_in_request')
     *      proverder8=> 'ignore',           // just like config('laravelfly.providers_ignore')
     */
    'providers_on_worker' => [

        // this is not in config('app.providers') and registered in Application:;registerBaseServiceProviders
        Illuminate\Log\LogServiceProvider::class => [
            'log' => true,
        ],

        Illuminate\Auth\AuthServiceProvider::class => [
            '_replaced_by' => LaravelFly\Map\Illuminate\Auth\AuthServiceProvider::class,
            'auth',
            GateContract::class,
        ],

        Illuminate\Broadcasting\BroadcastServiceProvider::class =>
            LARAVELFLY_SERVICES['broadcast'] ? [
                Illuminate\Broadcasting\BroadcastManager::class,
                Illuminate\Contracts\Broadcasting\Broadcaster::class,
            ] : 'ignore',

        Illuminate\Bus\BusServiceProvider::class => [],

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
            '_replaced_by' => LaravelFly\Map\Illuminate\Database\DatabaseServiceProvider::class,
            'db.factory',
            'db'
        ],

        Illuminate\Encryption\EncryptionServiceProvider::class => [
            'encrypter' => true,
        ],

        Illuminate\Filesystem\FilesystemServiceProvider::class => [
            'files' => true,
            'filesystem.disk' => true,
            'filesystem.cloud' => LARAVELFLY_SERVICES['filesystem.cloud'],
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

        /**
         * some static props like currentPathResolver, ... in Illuminate\Pagination\AbstractPaginator
         * in most cases they keep same.
         * if not same, `use StaticDict` is needed to convert AbstractPaginator in Map Mode.
         */
        Illuminate\Pagination\PaginationServiceProvider::class => [],

        Illuminate\Pipeline\PipelineServiceProvider::class => [],

        Illuminate\Queue\QueueServiceProvider::class => [],

        Illuminate\Redis\RedisServiceProvider::class => [
            'redis' => LARAVELFLY_SERVICES['redis'],
        ],

        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class => [],

        Illuminate\Session\SessionServiceProvider::class => [
            '_replaced_by' => LaravelFly\Map\Illuminate\Session\SessionServiceProvider::class,
            'session',
            'session.store',
            \Illuminate\Session\Middleware\StartSession::class
        ],

        Illuminate\Translation\TranslationServiceProvider::class => [
            'translation.loader' => true,
            'translator' => true,
        ],

        Illuminate\Validation\ValidationServiceProvider::class => [
            'validator' => true,
            'validation.presence' => true,
        ],

        Illuminate\View\ViewServiceProvider::class => [
            '_replaced_by' => \LaravelFly\Map\Illuminate\View\ViewServiceProvider::class,
            'view', 'view.engine.resolver', 'blade.compiler'
        ],


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
        // App\Providers\WorkerAppServiceProvider::class => [],

        /* depends */
        App\Providers\AuthServiceProvider::class => 'across',

        App\Providers\BroadcastServiceProvider::class => LARAVELFLY_SERVICES['broadcast'] ? [] : 'ignore',

        /* depends */
        /**
         * if it's register and boot need executing in each request, set to 'request' or move it to 'providers_in_request'
         * if it's events are always same in different request, set to true or [].
         */
        App\Providers\EventServiceProvider::class => 'across',

        /**
         * its boot loads files routes/web.php and routes/api.php.
         * see:
         *      Illuminate\Foundation\Support\Providers\RouteServiceProvider::boot()
         * and
         *      App\Providers\RouteServiceProvider::map
         */
        App\Providers\RouteServiceProvider::class => [],


        /*
         * Third Party
         */

        // Collision is an error handler framework for console/command-line PHP applications such as laravelfly
        NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class => [
            Illuminate\Contracts\Debug\ExceptionHandler::class => true,
        ],


        /*
         * LaravelFly
         */

        LaravelFly\Providers\RouteServiceProvider::class => [],
    ],

    /**
     * handle relations about cloned objects to avoid Stale Reference. For Mode Map
     *
     * clone and closure run in each request.
     */
    'update_for_clone' => [

        // for hash
        !empty(LARAVELFLY_SERVICES['hash']) ? false :
            [
                'this' => 'hash',
                'closure' => function () {
                    // $this here is app('hash'), the instance of HashManager
                    // by default, $name is bcrypt and argon
                    foreach ($this->getDrivers() as $name => $drive) {
                        $this->drivers[$name] = clone $drive;
                        // debug_zval_dump($this->drivers[$name] );
                    }
                },
            ],

        // put one more updating item here
        [
            // 'this' => 'name',
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
        // todo
//        \Illuminate\Session\Middleware\StartSession::class,
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

    /**
     * Which properties of base services need to backup. Only for Mode Simple
     *
     * See: Illuminate\Foundation\Application::registerBaseServiceProviders
     */
    'BaseServices' => [

        \Illuminate\Contracts\Http\Kernel::class => LARAVELFLY_SERVICES['kernel'] ? [] : [

            'middleware',

            /** depends
             * put new not safe properties here
             */
            // 'newProp1', 'newProp2',

        ],
        /* Illuminate\Events\EventServiceProvider::class : */
        'events' => [
            'listeners', 'wildcards', 'wildcardsCache', 'queueResolver',
        ],

        /* Illuminate\Routing\RoutingServiceProvider::class : */
        'router' => [
            /** depends
             * Uncomment them if it's not same on each request. They may be changed by Route::middleware
             */
            // 'middleware','middlewareGroups','middlewarePriority',

            /** depends */
            // 'binders',

            /** depends */
            // 'patterns',


            /** not necessary to backup,
             * // 'groupStack',
             */

            /** not necessary to backup,
             * it will be changed during next request
             * // 'current',
             */

            /** not necessary to backup,
             * the ref to app('request') will be released during next request
             * //'currentRequest',
             */

            /* Illuminate\Routing\RouteCollection */
            'obj.routes' => LARAVELFLY_SERVICES['routes'] ? [] : [
                'routes', 'allRoutes', 'nameList', 'actionList',
            ],
        ], /* end 'router' */

        'url' => [
            /* depends */
            // 'forcedRoot', 'forceScheme',
            // 'rootNamespace',
            // 'sessionResolver','keyResolver',
            // 'formatHostUsing','formatPathUsing',

            /** not necessary to backup,
             *
             * the ref to app('request') will be released during next request;
             * and no need set request for `url' on every request , because there is a $app->rebinding for request:
             *      $app->rebinding( 'request', $this->requestRebinder() )
             *
             * // 'request',
             *
             * auto reset when request is updated ( setRequest )
             * // 'routeGenerator','cachedRoot', 'cachedSchema',
             *
             * same as 'request'
             * // 'routes'
             */
        ],


        /** nothing need to backup
         *
         * // 'redirect' => false,
         * // 'routes' => false,
         * // 'log' => false,
         */
    ],
];

