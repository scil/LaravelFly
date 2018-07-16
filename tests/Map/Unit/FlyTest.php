<?php


namespace LaravelFly\Tests\Unit;


use LaravelFly\Tests\BaseTestCase;

class FlyTest extends BaseTestCase
{

    function testInitEnv()
    {
        self::assertEquals(false, defined('WORKER_COROUTINE_ID '));

        $initEnv = new \ReflectionMethod('\LaravelFly\Fly', 'initEnv');
        $initEnv->setAccessible(true);
        $initEnv->invoke(null,[]);

        self::assertEquals(true, defined('WORKER_COROUTINE_ID'));
        self::assertEquals(true, function_exists('tinker'));


    }

}