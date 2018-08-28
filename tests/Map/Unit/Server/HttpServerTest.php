<?php
/**
 * User: scil
 * Date: 2018/8/28
 * Time: 18:51
 */

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Server\HttpServer;
use LaravelFly\Tests\BaseTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class HttpServerTest extends BaseTestCase
{
    const port = '9503';

    const baseUrl = '/laravelfly-test/';

    const curlBaseUrl = '127.0.0.1:' . (self::port) . self::baseUrl;

    function test()
    {
        $this->requestTest(
            function () {
                return \Request::path();
            },
            'laravelfly-test/test1',
            'test1'
        );
    }

    function requestTest($func, $result, $endUrl = 'test1')
    {

        $appRoot = static::$laravelAppRoot;

        $constances = [
        ];

        $options = [
            'worker_num' => 1,
            'mode' => 'Map',
            'listen_port' => self::port,
            'daemonize' => false,
            'pre_include' => false,
        ];

        $routeUrl = static::baseUrl . $endUrl;

        $r = self::request($constances, $options,

            [
                static::curlBaseUrl.$endUrl,
            ],

            function (HttpServer $server) use ($routeUrl, $func) {

                $server->getDispatcher()->addListener('worker.ready', function () use ($routeUrl, $func) {
                    \Route::get($routeUrl, $func);
                });

                $server->start();

            }, 3);

        self::assertEquals($result, $r);
    }

}