<?php

// Normal Mode or Greedy Mode
const LARAVELFLY_GREEDY = true;

/**
 * When to load 'compiled.php'
 * If true, compiled.php is loaded before any swoole worker. No matter how many workers, there's only one copy in memory. The merit is saving some memory . The demerit is , memory does not update when you restart all workers.
 * If false, compiled.php is loaed on each worker start.Each worker has compiled codes in memory. The merit is , if compiled.php changes, you can restart all workers to use new codes.
 */
const LOAD_COMPILED_BEFORE_WORKER = false;

// when true, Application::runningInConsole() return false.
const FAKE_NOT_IN_CONSOLE = false;

// this array is used for swoole server, see more option list at swoole doc.
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
];
