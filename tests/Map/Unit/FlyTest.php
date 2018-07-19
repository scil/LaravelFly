<?php


namespace LaravelFly\Tests\Unit;


use LaravelFly\Tests\BaseTestCase;

class FlyTest extends BaseTestCase
{

    function testInitEnv()
    {
        $r = $this->process(function () {
            $r[]= defined('WORKER_COROUTINE_ID ');

            $initEnv = new \ReflectionMethod('\LaravelFly\Fly', 'initEnv');
            $initEnv->setAccessible(true);
            $initEnv->invoke(null, []);

            $r[]= defined('WORKER_COROUTINE_ID');
            $r[]= function_exists('tinker');

            return $r;
        });

        self::assertEquals([false, true, true], json_decode($r));

    }

}