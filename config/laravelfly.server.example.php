<?php

const LARAVELFLY_KERNEL= '\App\Http\Kernel';

// Normal Mode or Greedy Mode
const LARAVELFLY_GREEDY = true;

/**
 * When to load 'compiled.php'. Only for <= laravel 5.4
 * If true, compiled.php is loaded before any swoole worker. No matter how many workers, there's only one copy in memory. The merit is saving some memory . The demerit is , memory does not update when you restart all workers.
 * If false, compiled.php is loaed on each worker start.Each worker has compiled codes in memory. The merit is , if compiled.php changes, you can restart all workers to use new codes.
 * For Laravel 5.5+, set null
 */
const LOAD_COMPILED_BEFORE_WORKER = null;

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

    // like pm.max_children in php-fpm, but there's no option like pm.start_servers
    'worker_num' => 4,

    // set it to false when debug, otherwise true
    'daemonize' => true,

    // like pm.max_requests in php-fpm
    'max_request' => 1000,

    //'log_file' => '/data/log/swoole.log',

    // Set the output buffer size in the memory.
    // The default value is 2M. The data to send can't be larger than buffer_output_size every times.
    //'buffer_output_size' => 32 * 1024 *1024, // byte in unit

    //'group' => 'www-data',
];
