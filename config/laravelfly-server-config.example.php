<?php

/**
 * Simple, Coroutine or Greedy
 * Greedy only for study
 */
const LARAVELFLY_MODE = 'Simple';

/**
 * Tell application it's running in cli mode.
 *
 * Some serivces, such as DebugBar, not run in cli mode.
 * Set it false, Application::runningInConsole() return false, and DebugBar can start.
 */
const HONEST_IN_CONSOLE = true;

/**
 * make some services on worker, before any requests, to save memory
 *
 * only for Mode Coroutine and advanced users
 *
 * A COROUTINE-FRIENDLY SERVICE must satisfy folling conditions:
 * 1. singleton. A singleton service is made by by {@link Illuminate\Containe\Application::singleton()} or {@link Illuminate\Containe\Application::instance() }
 * 2. its vars will not changed in any requests
 * 3. if it has ref attibutes, like app['events'] has an attribubte `container`, the container must be also A COROUTINE-FRIENDLY SERVICE
 */
const LARAVELFLY_SINGLETON= [
    "redis" => false,   // to true if 'redis' is used
    'filesystem.cloud' => false,   // to true if 'filesystem.cloud' is used
    'hash' => false,  // to true if app('hash')->setRounds is never called in any requests
    'view.engine.resolver' => false,  // to true if app('view.engine.resolver')->register is never called in any requests. See: Illuminate\View\Engines::register
];

/**
 * this array is used for swoole server,
 * see more option list at :
 * 1. Swoole HTTP server configuration https://www.swoole.co.uk/docs/modules/swoole-http-server/configuration
 * 2. Swoole server configuration https://www.swoole.co.uk/docs/modules/swoole-server/configuration
 */
return [
    // 'listen_ip' => '0.0.0.0',// listen to any address
    'listen_ip' => '127.0.0.1',// listen only to localhost

    'listen_port' => 9501,

    // like pm.start_servers in php-fpm, but there's no option like pm.max_children
    'worker_num' => 4,

    // set it to false when debug, otherwise true
    'daemonize' => true,

    // like pm.max_requests in php-fpm
    'max_request' => 1000,

    //'group' => 'www-data',

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


    'kernel' => \App\Http\Kernel::class,
];
