<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Tests\Map\BaseTestCase;

class CommonTest extends BaseTestCase
{
    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServer;

    static $default = [];

    /**
     * This method is called before each test.
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$commonServer = new \LaravelFly\Server\Common();

        $d = new \ReflectionProperty(static::$commonServer, 'defaultOptions');
        $d->setAccessible(true);
        static::$default = $d->getValue(static::$commonServer);
    }

    function resetConfig()
    {
        $c = new \ReflectionProperty(static::$commonServer, 'options');
        $c->setAccessible(true);
        $c->setValue(static::$commonServer,[]);

    }

    function testKernalClass()
    {

        $this->resetConfig();

        $k= new \ReflectionProperty(static::$commonServer,'kernelClass');
        $k->setAccessible(true);

        static::$commonServer->config([ 'compile' => false]);
        self::assertEquals('App\Http\Kernel', $k->getValue(static::$commonServer));

    }
}