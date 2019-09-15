<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Server\HttpServer;
use LaravelFly\Tests\BaseTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class HttpServerTest extends BaseTestCase
{

    function testonOnupRequestGet()
    {
        $this->assertResponsePassingRoutes(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {
                        return \Request::path();
                    }
                ],
                [
                    'get',
                    static::testBaseUrl . 'test2',
                    function () {
                        return \Request::query('name');
                    }
                ],
            ],
            [
                static::testCurlBaseUrl . 'test1',
                static::testCurlBaseUrl . 'test2?name=scil',
            ],
            [
                'laravelfly-test/test1',
                'scil'
            ]
        );

    }

    function testonOnRequestPost()
    {
        $this->assertResponsePassingRoutes(
            [
                [
                    'post',
                    static::testBaseUrl . 'test1',
                    function () {
                        return \Request::path();
                    }
                ],
                [
                    'post',
                    static::testBaseUrl . 'test2',
                    function () {
                        return \Request::query('name');
                    }
                ],
            ],
            [
                [
                    'url' => static::testCurlBaseUrl . 'test1',
                    'options' => [
                        CURLOPT_POST => 1,
                    ]
                ],
                [
                    'url' => static::testCurlBaseUrl . 'test2?name=scil',
                    'options' => [
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => ['name' => 'scil', 'password' => 'passuser1', 'gender' => 1],
                    ]
                ]
            ],
            [
                'laravelfly-test/test1',
                'scil'
            ]
        );

    }

    function testHeader()
    {
        $this->assertResponsePassingRoutes(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {
                        return \Request::header('accept');
                    }
                ],
            ],
            [
                [
                    'url' => static::testCurlBaseUrl . 'test1',
                    'options' => [
                    ]
                ]
            ],
            [
                '*/*',
            ]
        );

    }
}