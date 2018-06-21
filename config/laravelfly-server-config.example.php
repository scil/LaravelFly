<?php

/**
 * Simple, Map, FpmLike
 *
 * FpmLike: like php-fpm, objects are made in each request.Warning: currently there's no
 */
if(!defined('LARAVELFLY_MODE')) define('LARAVELFLY_MODE',
    'Simple'
);

/**
 * honest that application is running in cli mode.
 *
 * Some serivces, such as DebugBar, not run in cli mode.
 * Some service providers, such as MailServiceProvider, get ready to publish  resources in cli mode.
 *
 * Set it true, Application::runningInConsole() return true, and DebugBar can not start.
 * If you use FpmLike, must keep it false.
 */
if(!defined('HONEST_IN_CONSOLE')) define('HONEST_IN_CONSOLE',
    false
);

/**
 * make some services on worker, before any requests, to save memory
 *
 * mainly for Mode Mape with only one exception that item 'config' is also for Mode Simple.
 *
 * A COROUTINE-FRIENDLY SERVICE must satisfy folling conditions:
 * 1. singleton. A singleton service is made by by {@link Illuminate\Containe\Application::singleton()} or {@link Illuminate\Containe\Application::instance() }
 * 2. its vars will not changed in any requests
 * 3. if it has ref attibutes, like app['events'] has an attribubte `container`, the container must be also A COROUTINE-FRIENDLY SERVICE
 */
if(!defined('LARAVELFLY_SERVICES')) define('LARAVELFLY_SERVICES',[

    /**
     * set this false if items in app('config') different across different requests, because
     * for Mode Simple, it's necessary to restore its origin value after each request.
     * for Mode Map, it's necessary to convert Illuminate\Config\Repository friendly to coroutine.
     *
     * In most cases, it's not necessary to set it false. Except for old versions of Debugbar
     * which changes 'debugbar.enabled' from true to false after its booting, so it's necessary to
     * restore its origin value to allow Debugbar continue work in other requests.
     *
     */
    'config' => true,


    /**
     * set the corresponding service to be true if you use it.
     */
    "redis" => false,
    'filesystem.cloud' => false,
    'broadcast' => false,

    // set false if app('hash')->setRounds may be called in a request. If we want to make 'hash' COROUTINE-FRIENDLY, it should be always same.
    'hash' => true,

    /**
     * set false if same view name refers to different view files in different requests.
     * for example:
     *      view 'home' may points to 'guest_location/home.blade.php' for a guest ,
     *      while to 'admin_location/home.blade.php' for an admin
     */
    'view.finder' => true,

]);

/**
 * this array is used for swoole server,
 * see more option list at :
 * 1. Swoole HTTP server configuration https://www.swoole.co.uk/docs/modules/swoole-http-server/configuration
 * 2. Swoole server configuration https://www.swoole.co.uk/docs/modules/swoole-server/configuration
 */
return [
    /**
     * provided by LaravelFly:
     *      \LaravelFly\Server\HttpServer::class
     *      \LaravelFly\Server\WebSocketServer::class  // still under dev
     *
     * when LARAVELFLY_MODE == 'FpmLike', this is ignored and \LaravelFly\Server\FpmHttpServer::class is used.
     */
    'server' => \LaravelFly\Server\HttpServer::class,

    /**
     * true if you use eval(tinker())
     *
     * note:
     * 1. this tinker ignores config('tinker.dont_alias', []), because it starts before app created
     * 2. If see an error about mkdir, please start LaravelFly using sudo.
     */
    'tinker' => false,

    /**
     * this is not for \LaravelFly\Server\WebSocketServer which always uses '0.0.0.0'
     * extend it and overwrite its __construct() if you need different listen_ip,
     */
    // 'listen_ip' => '127.0.0.1',// listen only to localhost
     'listen_ip' => '0.0.0.0',// listen to any address

    'listen_port' => 9501,

    // like pm.start_servers in php-fpm, but there's no option like pm.max_children
    'worker_num' => 5,

    // max number of coroutines handled by a worker in the same time
    'max_coro_num' => 3000,

    // set it to false when debug, otherwise true
    // if you use tinker(), daemonize is disabled always.
    'daemonize' => false,

    // like pm.max_requests in php-fpm
    'max_request' => 1000,

    /**
     *  watch files or dirs for server hot reload.
     *
     * When any of the files or dirs change,all of the workers would finish their work and quit,
     * then new workers are created. All of the files loaded in a worker would load again.
     *
     * This featue is equivalent to `php vendor/scil/laravel-fly/bin/fly reload`, but requires:
     *  1. absolute path.
     *  2. run LaravelFly as root: `sudo php vendor/scil/laravel-fly/bin/fly start` and ensure the 'user' configed here is a member of root group
     *  3. `pecl install inotify`
     *
     * note: inotify not support files mounted in virtualbox machines.
     * (see:https://github.com/moby/moby/issues/18246)
     * A solution is to watch a file like `/home/vagrant/.watch`, and modify it when your codes change.
     */
    'watch'=>[
//        __DIR__.'/app',
//        __DIR__.'/config',
//        __DIR__.'/resources/views',
//        __DIR__.'/routes/web.php',
    ],
    /**
     * how long after code changes the server hot reload
     * default is 1500ms
     */
    'watch_delay'=> 1500,

    /**
     * include laravel's core files befor server starts
     *
     * The core files will not support hot reload because they are included before workers start.
     * This is not big loss when LaravelFly is working with nginx/apache together.
     * There's are some nginx conf examples to let php-fpm handles request when LaravelFly is restarting.
     *
     */
    'pre_include' =>true,

    /**
     * Add more files to be pre-included
     *
     * note:
     * 1. order is important
     * 2. The files will not support hot reload
     */
    'pre_files' => [],

    /**
     * If Laravel Application instanced before server starts.
     *
     * no need worry about memory leak. Because the Laravel Application instance would be copied into
     * a new process when each swoole worker started.
     *
     * It's good for production env.
     * The little loss is that it make LaravelFly not supporting hot reload, like 'pre_include'.
     */
    'early_laravel' => false,

    /**
     * In each worker process, Larave log will not write log files until the number of log records reach 'log_cache'
     *
     * only for "single" or "daily" log.
     *
     * the max of log cache recoders in the server is:  log_cache * worker_num.
     *
     * no worry log lost, the cache will be written through event 'worker.stopped' when server stop or reload,
     *
     * related source code file: vendor/scil/laravel-fly/src/fly/StreamHandler.php
     *
     * 0, 1 or false to disable it
     */
    'log_cache' => 5,

    /**
     * if you use more than one workers, you can control which worker handle a request
     * by sending query parameter or header
     *
     * by worker id // range: [0, worker_num)
     * use worker 0:
     *      curl zhenc.test/hi?worker-id=0
     *      curl zhenc.test/hi  --header "Worker-Id: 0"
     * use worker 1
     *      curl zhenc.test/hi?worker-id=1
     *      curl zhenc.test/hi  --header "Worker-Id: 1"
     *
     * by worker process id
     *      curl zhenc.test/fly?worker-pid=14791
     *      curl zhenc.test/hi  --header "Worker-Pid: 14791"
     *
     * It's useful if you want to use eval(tinker()) in different worker process.
     * All vars available in a tinker shell are almost only objects in the worker process which the tinker is running
     *
     * Please do not enalbe it in production env.
     */
    'dispatch_by_query'=>false,

    /**
     * only works when running LaravelFly as root:
     *      sudo php vendor/scil/laravel-fly/bin/fly start
     *
     *
     * ensure the user or the group can read/write the Laravel project.
     * It's not appropriate that the user/group can read a dir/file such as '/www/app/some',but can not read the the root /www
     *
     * If you use watch, disable these, or ensure the user here is a member of group root
     * /
    // 'user' => 'www-data',
    // 'group' => 'www-data',

    //'log_file' => '/data/log/swoole.log',

    /** Set the output buffer size in the memory.
     * The default value is 2M. The data to send can't be larger than buffer_output_size every times.
     */
    //'buffer_output_size' => 32 * 1024 *1024, // byte in unit


    /**
     * make sure the pid_file can be writeable/readable by vendor/bin/laravelfly-server
     * otherwise use `sudo vendor/bin/laravelfly-server` or `chmod -R 777 <pid_dir>`
     *
     * default is under <project_root>/bootstrap/
     */
    //'pid_file' => '/run/laravelfly/pid',

    /**
     * make sure this kernel class extends \LaravelFly\Simple\Kernel or
     * \LaravelFly\Map\Kernel,
     * otherwise \LaravelFly\Kernel::class is used.
     *
     * A simple way it to edit app/Http/Kernel.php like the guide in
     * https://github.com/scil/LaravelFly/blob/master/doc/config.md
     */
    'kernel' => \App\Http\Kernel::class,

];
