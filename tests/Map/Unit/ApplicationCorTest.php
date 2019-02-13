<?php

namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\BaseTestCase;

class ApplicationCorTest extends BaseTestCase
{

    function testGo()
    {

        $this->assertResponsePassingRoutes(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {


                        $log = \Request::get('log', false);
                        $a = [];
                        go(function () use (&$a, $log) {
                            $a[] = 'go1';
                            \co::sleep(2);
                            $a[] = 'go1.end';
                            if ($log) {
                                $logFile = app()->storagePath() . '/logs/laravel.log';
                                file_put_contents($logFile, implode(';', $a));
                            }
                        });

                        $a[] = 'outer1';

                        go(function () use (&$a) {
                            $a[] = 'go2';
                            \co::sleep(1.2);
                            $a[] = 'go2.end';
                        });

                        $a[] = 'outer2';

                        \co::sleep((float)\Request::get('wait', 1));

                        $a[] = 'outer3';

                        return implode(';', $a);
                    }
                ],
            ],
            [
                static::testCurlBaseUrl . 'test1?wait=3',
                static::testCurlBaseUrl . 'test1?wait=1.5',
                static::testCurlBaseUrl . 'test1?wait=1&log=1',
            ],
            [
                "go1;outer1;go2;outer2;go2.end;go1.end;outer3",
                'go1;outer1;go2;outer2;go2.end;outer3',
                "go1;outer1;go2;outer2;outer3",
            ],
            function () {
            },
            5
        );

        $log = file_get_contents(LARAVEL_APP_ROOT . '/storage/logs/laravel.log');
        self::assertEquals('go1;outer1;go2;outer2;outer3;go2.end;go1.end', $log);

    }


    function testFlyFunction()
    {

        $this->assertResponsePassingRoutes(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {
                        $a = '';

                        fly(function () use (&$a) {
                            \Log::info(\Request::path());
                            $a = \Request::path();
                        });


                        return $a;
                    }
                ],
            ],
            [
                static::testCurlBaseUrl . 'test1',
            ],
            [
                substr(static::testBaseUrl, 1) . 'test1',
            ],
            function () {
            },
            1
        );

        $log = $this->getLastLog();
        self::assertEquals('laravelfly-test/test1', $log);
    }

    function testFlyComplicated()
    {

        $this->assertResponsePassingRoutes(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {

                        $log = \Request::get('log', false);
                        $a = [];
                        fly(function () use (&$a, $log) {
                            $a[] = 'go1';
                            \co::sleep(2);
                            $a[] = 'go1.end';
                            if ($log) {
                                \Log::info(implode(';', $a));
                            }
                        });

                        $a[] = 'outer1';

                        go(function () use (&$a) {
                            $a[] = 'go2';
                            \co::sleep(1.2);
                            $a[] = 'go2.end';
                        });

                        $a[] = 'outer2';

                        \co::sleep((float)\Request::get('wait', 1));

                        $a[] = 'outer3';

                        return implode(';', $a);
                    }
                ],
            ],
            [
                static::testCurlBaseUrl . 'test1?wait=3',
                static::testCurlBaseUrl . 'test1?wait=1.5',
                static::testCurlBaseUrl . 'test1?wait=1&log=1',
            ],
            [
                "go1;outer1;go2;outer2;go2.end;go1.end;outer3",
                'go1;outer1;go2;outer2;go2.end;outer3',
                "go1;outer1;go2;outer2;outer3",
            ],
            function () {
            },
            5
        );

        self::assertEquals('go1;outer1;go2;outer2;outer3;go2.end;go1.end', $this->getLastLog());

    }


}