<?php


namespace LaravelFly\Tests\Unit;


use LaravelFly\Tests\BaseTestCase;

class FlyTest extends BaseTestCase
{

    function testInitEnv()
    {
        $r = self::process(function () {
            $r[] = defined('WORKER_COROUTINE_ID ');

            $initEnv = new \ReflectionMethod('\LaravelFly\Fly', 'initEnv');
            $initEnv->setAccessible(true);
            $initEnv->invoke(null, []);

            $r[] = defined('WORKER_COROUTINE_ID');
            $r[] = WORKER_COROUTINE_ID;
            $r[] = function_exists('tinker');

            return $r;
        });

        self::assertEquals([false, true, -1, true], json_decode($r));

    }

    function testEarly_laravel()
    {
        $r = self::process(function () {
            $r[] = defined('WORKER_COROUTINE_ID ');

            $initEnv = new \ReflectionMethod('\LaravelFly\Fly', 'initEnv');
            $initEnv->setAccessible(true);
            $initEnv->invoke(null, ['early_laravel'=>true]);

            $r[] = defined('WORKER_COROUTINE_ID');
            $r[] = WORKER_COROUTINE_ID;
            $r[] = function_exists('tinker');

            return $r;
        });

        self::assertEquals([false, true, -1, true], json_decode($r));

    }

}