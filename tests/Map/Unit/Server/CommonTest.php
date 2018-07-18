<?php

namespace LaravelFly\Tests\Map\Unit\Server;


class CommonTest extends CommonServerTestCase
{
    /**
     * This method is called before each test.
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //static::$commonServer = new \LaravelFly\Server\Common();
        /**
         * create a server and get default server options
         */

        $d = new \ReflectionProperty(static::$commonServerNoSwoole, 'defaultOptions');
        $d->setAccessible(true);
        static::$default = $d->getValue(static::$commonServerNoSwoole);
    }

    function testInit()
    {
        $server = static::$commonServerNoSwoole;

        $root = new \ReflectionProperty($server, 'root');
        $root->setAccessible(true);
        self::assertEquals(static::$laravelAppRoot, $root->getValue($server));
    }

    function testAppClass()
    {
        $this->resetServerConfigAndDispatcher(static::$commonServerNoSwoole);

        $a = new \ReflectionProperty(static::$commonServerNoSwoole, 'appClass');
        $a->setAccessible(true);

        foreach ([
                     'Map',
//                     'Simple',
//                     'FpmLike'
                 ] as $mode) {
            static::$commonServerNoSwoole->config(['mode' => $mode, 'pre_include' => false]);
            $appClass = $a->getValue(static::$commonServerNoSwoole);
            self::assertEquals("\LaravelFly\\$mode\Application", $appClass);
        }

    }

    function testDefaultOptions()
    {
        $this->resetServerConfigAndDispatcher(static::$commonServerNoSwoole);

        $server = static::$commonServerNoSwoole;


        $server->config([]);

        $actual = $server->getConfig();
        self::assertEquals(true, isset($actual['pid_file']));

        self::assertEquals('Map', $actual['mode']);

        unset($actual['pid_file']);
        self::assertEquals(static::$default, $actual);


    }

    function testMergeOptions()
    {
        $this->resetServerConfigAndDispatcher(static::$commonServerNoSwoole);

        $server = static::$commonServerNoSwoole;

        self::assertEquals(null, $server->getConfig('listen_ip'));
        self::assertEquals('0.0.0.0', static::$default['listen_ip']);

        // compile should be false, as compiled files has been included in the $server->config([]) above
        $server->config(['listen_ip' => '127.0.0.1', 'pre_include' => false]);
        // changed
        self::assertEquals('127.0.0.1', $server->getConfig('listen_ip'));
        // no change
        self::assertEquals(static::$default['worker_num'], $server->getConfig('worker_num'));
    }

    function testConfigPidFile()
    {
        static::$commonServerNoSwoole->config(['pre_include' => false]);
        self::assertEquals(static::$laravelAppRoot . '/bootstrap/laravel-fly-9501.pid', static::$commonServerNoSwoole->getConfig('pid_file'));

    }


    /**
     * kernelClass always be \LaravelFly\Kernel, because
     * 'App\Http\Kernel' has included in previous tests , and const LARAVELFLY_MODE not defined
     *
     * @throws \ReflectionException
     */
    function testKernalClass()
    {

        $this->resetServerConfigAndDispatcher(static::$commonServerNoSwoole);

        $k = new \ReflectionProperty(static::$commonServerNoSwoole, 'kernelClass');
        $k->setAccessible(true);

        $flyKernel = 'LaravelFly\Kernel';

        static::$commonServerNoSwoole->config(['pre_include' => false]);
        //self::assertEquals($flyKernel, $k->getValue(static::$commonServer));
        self::assertEquals('App\Http\Kernel', $k->getValue(static::$commonServerNoSwoole));

    }


    function testPath()
    {
        self::assertEquals(static::$laravelAppRoot, static::$commonServerNoSwoole->path());

        self::assertEquals(static::$laravelAppRoot . '/bootstrap/', static::$commonServerNoSwoole->path('bootstrap/'));
    }

    function testGetMemory()
    {
        self::assertEquals(null, static::$commonServerNoSwoole->getMemory('no-exist'));
    }
}