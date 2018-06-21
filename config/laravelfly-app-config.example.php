<?php

if (!defined('LARAVELFLY_MODE')) return [];

return [
    /**
     * For each worker, if a view file is compiled max one time. Only For Mode Map
     *
     * If true, Laravel not know a view file changed until the swoole workers restart.
     * It's good for production env.
     */
    'view_compile_1' => LARAVELFLY_SERVICES['view.finder'] &&
        (env('APP_ENV') === 'production' || env('APP_ENV') === 'product'),

    /**
     * useless providers. For Mode Simple, Map
     *
     * There providers will be removed from app('config')['app.providers'] on worker, before any requests
     */
    'providers_ignore' => array_merge([
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Laravel\Tinker\TinkerServiceProvider::class,
        Fideloper\Proxy\TrustedProxyServiceProvider::class,
        LaravelFly\Providers\CommandsServiceProvider::class,
        'Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider',
    ], LARAVELFLY_SERVICES['broadcast'] ? [
        Illuminate\Broadcasting\BroadcastManager::class,
        Illuminate\Contracts\Broadcasting\Broadcaster::class,
        App\Providers\BroadcastServiceProvider::class
    ] : []),

    /**
     * Providers to reg and boot in each request.For Mode Simple, Map
     *
     * There providers will be removed from app('config')['app.providers'] on worker, before any requests
     */
    'providers_in_request' => [
    ],


    /**
     * Which properties of base services need to backup. Only for Mode Simple
     *
     * See: Illuminate\Foundation\Application::registerBaseServiceProviders
     */
    'BaseServices' => [

        /* Illuminate\Events\EventServiceProvider::class : */
        'events' => [
            'listeners', 'wildcards', 'queueResolver',
        ],

        /* Illuminate\Routing\RoutingServiceProvider::class : */
        'router' => [
            /** depends
             * Uncomment them if it's not same on each request. They may be changed by Route::middleware
             */
            // 'middleware','middlewareGroups','middlewarePriority',

            /** not necessary to backup,
             * it will be changed during next request
             * // 'current',
             */

            /** not necessary to backup,
             * the ref to app('request') will be released during next request
             * //'currentRequest',
             */

            'obj.routes' => [
                /** depends
                 *
                 * Uncomment them if some of your routes are created during any request.
                 * Besides, because values of these four properties are associate arrays,
                 * if names of routes created during request are sometime different , please uncomment them ,
                 */
                // 'routes' , 'allRoutes' , 'nameList' , 'actionList' ,
            ],
        ], /* end 'router' */

        'url' => [
            /* depends */
            // 'forcedRoot', 'forceSchema',
            // 'cachedRoot', 'cachedSchema',

            /** not necessary to backup,
             *
             * the ref to app('request') will be released during next request;
             * and no need set request for `url' on every request , because there is a $app->rebinding for request:
             *      $app->rebinding( 'request', $this->requestRebinder() )
             * //'request'
             */
        ],


        /** nothing need to backup
         *
         * // 'redirect' => false,
         * // 'routes' => false,
         * // 'log' => false,
         */
    ],

    /**
     * providers to reg and boot on worker, before any request. only for Mode Map
     *
     * format:
     *      proverder_name => [],
     *
     * you can also supply singleton services to made on worker
     * only singleton services are useful and valid here.
     * and the singleton services must not be changed during any request,
     * otherwise they should be made in request, no on worker.
     *
     * a singeton service is like this:
     *     *   $this->app->singleton('hash', function ($app) { ... });
     *
     * formats:
     *      proverder,                   // this provider will be booted on worker
     *      proverder2=> true,           // this provider will be booted on worker
     *      proverder1=> [],             // this provider will be booted on worker
     *      proverder3=> [
     *        '_replace' => 'provider1', // the provider1 will be replaced by provider2 and deleted from app['config']['app.providers']
     *        'singleton_service_1' => true,  //  service will be made on worker
     *        'singleton_service_2' => false, //  service will not be made on worker,
     *                                            even if the service has apply if using coroutineFriendlyServices()
     *      ],
     *
     *      proverder4=> false,           // this provider will not be booted on worker
     *      proverder5=> null,           // this provider will not be booted on worker too.
     */
    'providers_on_worker' => [
        // this is not in config('app.providers') and registered in Application:;registerBaseServiceProviders
        Illuminate\Log\LogServiceProvider::class => [
            'log' => true,
        ],
        LaravelFly\Map\Illuminate\Auth\AuthServiceProvider::class => [
            '_replace' => Illuminate\Auth\AuthServiceProvider::class,
        ],
        Illuminate\Broadcasting\BroadcastServiceProvider::class =>
            LARAVELFLY_SERVICES['broadcast'] ? [
                Illuminate\Broadcasting\BroadcastManager::class,
                Illuminate\Contracts\Broadcasting\Broadcaster::class,
            ] : false,
        Illuminate\Bus\BusServiceProvider::class => [],
        Illuminate\Cache\CacheServiceProvider::class => [
            'cache' => true,
            'cache.store' => true,
            /* depends */
            // if memcached is used, enable it
            // 'memcached.connector' => true,

        ],
        LaravelFly\Map\Illuminate\Cookie\CookieServiceProvider::class => [
            '_replace' => Illuminate\Cookie\CookieServiceProvider::class,
        ],
        LaravelFly\Map\Illuminate\Database\DatabaseServiceProvider::class => [
            '_replace' => Illuminate\Database\DatabaseServiceProvider::class,
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
        // Illuminate\Foundation\Providers\FoundationServiceProvider::class=>[] : providers_across ,
        Illuminate\Hashing\HashServiceProvider::class => [
            'hash' => LARAVELFLY_SERVICES['hash']
        ],
        Illuminate\Mail\MailServiceProvider::class => [],

        // Illuminate\Notifications\NotificationServiceProvider::class,

        Illuminate\Pagination\PaginationServiceProvider::class => [],

        Illuminate\Pipeline\PipelineServiceProvider::class => [],
        Illuminate\Queue\QueueServiceProvider::class => [],
        Illuminate\Redis\RedisServiceProvider::class => [
            'redis' => LARAVELFLY_SERVICES['redis'],
        ],
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class => [],
        LaravelFly\Map\Illuminate\Session\SessionServiceProvider::class => [
            '_replace' => Illuminate\Session\SessionServiceProvider::class,
        ],
        Illuminate\Translation\TranslationServiceProvider::class => [
            'translation.loader' => true,
            'translator' => true,
        ],
        Illuminate\Validation\ValidationServiceProvider::class => [
            'validator' => true,
            'validation.presence' => true,
        ],
        \LaravelFly\Map\Illuminate\View\ViewServiceProvider::class => [
            '_replace' => Illuminate\View\ViewServiceProvider::class,
        ],
        /*
         * Application Service Providers...
         */
        /* depends */
        /**
         * if it's register and boot need executing in each request, remove it to 'providers_in_request'
         * if only it's boot needs executing in each request, comment it.
         */
        App\Providers\AppServiceProvider::class => [],

        /* depends */
        //todo
        App\Providers\AuthServiceProvider::class => false,

        App\Providers\BroadcastServiceProvider::class => LARAVELFLY_SERVICES['broadcast'] ? [] : false,

        /* depends */
        App\Providers\EventServiceProvider::class => [],

        /* depends */
        App\Providers\RouteServiceProvider::class => [],

        // Collision is an error handler framework for console/command-line PHP applications such as laravelfly
        NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class => [
            Illuminate\Contracts\Debug\ExceptionHandler::class => true,
        ],

    ],

];

