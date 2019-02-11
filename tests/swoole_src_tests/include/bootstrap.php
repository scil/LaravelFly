<?php
ini_set("memory_limit", "1024M");
ini_set('swoole.display_errors', 'Off');

ini_set("assert.active", 1);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL, 0);
assert_options(ASSERT_QUIET_EVAL, 0);

if (method_exists('co', 'set')) {
    Co::set([
        'log_level' => SWOOLE_LOG_INFO,
        'trace_flags' => 0,
        'socket_timeout' => 5,
    ]);
}


require_once __DIR__ . '/swoole.inc';