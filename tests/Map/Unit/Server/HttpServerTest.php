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
            [
                static::baseUrl . 'test1' => function () {
                    return \Request::path();
                },
                static::baseUrl . 'test2' => function () {
                    return \Request::query('name');
                },
            ],
            [
                'test1' => 'laravelfly-test/test1',
                'test2?name=scil' => 'scil',
            ]
        );

    }

    function requestTest($routes, $curlPair)
    {

        foreach ($curlPair as $url => $result) {
            $urls[] = static::curlBaseUrl . $url;
            $results [] = $result;
        }

        $constances = [
        ];

        $options = [
            'worker_num' => 1,
            'mode' => 'Map',
            'listen_port' => self::port,
            'daemonize' => false,
            'pre_include' => false,
        ];

        $r = self::request($constances, $options, $urls,


            function (HttpServer $server) use ($routes) {

                $server->getDispatcher()->addListener('worker.ready', function () use ($routes) {
                    foreach ($routes as $url => $func) {
                        \Route::get($url, $func);
                    }
                });

                $server->start();

            }, 3);

        self::assertEquals(implode("\n",$results), $r);
    }

}