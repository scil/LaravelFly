<?php

namespace LaravelFly\Tests\Unit\Server;

use LaravelFly\Tests\BaseTestCase;

class CommonTest extends BaseTestCase
{
    /**
     * @var \LaravelFly\Server\Common;
     */
    var $commonServer;

    /**
     * This method is called before each test.
     */
    protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::setUp();
        $this->commonServer = new \LaravelFly\Server\Common();
    }


    function testInit()
    {
        $server = $this->commonServer;

        $root = new \ReflectionProperty($server, 'root');
        $root->setAccessible(true);
        self::assertEquals(static::$root, $root->getValue($server));
    }

    function testDefaultOptions()
    {
        $server= $this->commonServer;
        $server->config([]);

        $root = new \ReflectionProperty($server, 'root');
        $root->setAccessible(true);
        $default = $root->getValue($server);

        self::assertEquals($default,$server->getConfig());

    }
}