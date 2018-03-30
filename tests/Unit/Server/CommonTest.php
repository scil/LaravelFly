<?php

namespace LaravelFly\Tests\Unit\Server;

use LaravelFly\Tests\BaseTestCase;

class CommonTest extends BaseTestCase
{

    function testInit()
    {
       $server = new \LaravelFly\Server\Common();

       $root= new \ReflectionProperty($server,'root');
       $root->setAccessible(true);
       self::assertEquals(static::$root, $root->getValue($server));
    }
}