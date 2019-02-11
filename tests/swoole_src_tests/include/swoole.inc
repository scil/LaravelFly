<?php
require_once  __DIR__ . "/config.php";
require_once __DIR__ . "/toolkit/RandStr.php";
require_once __DIR__ . "/toolkit/functions.php";
require_once __DIR__ . '/../include/lib/curl.php';


swoole_async_set([
    "socket_dontwait" => 1,
    "thread_num" => 1,
    'disable_dns_cache' => true,
    'dns_lookup_random' => true,
]);