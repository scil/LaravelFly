<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Server\HttpServer;
use LaravelFly\Tests\BaseTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class HttpServerTest extends BaseTestCase
{

    function testonOnBackupRequest()
    {
        $this->requestForTest(
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
                'test1' => 'laravelfly-test/test1',
                'test2?name=scil' => 'scil',
            ]
        );

    }

}