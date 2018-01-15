<?php

const LARAVELFLY_KERNEL= \App\Http\Kernel::class;

/**
 * Normal, Coroutine or Greedy
 * Greedy only for study
 */
const LARAVELFLY_MODE = 'Normal';

// when false, Application::runningInConsole() return false.
const HONEST_IN_CONSOLE = true;

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
];
