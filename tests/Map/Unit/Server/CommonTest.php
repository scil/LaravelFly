<?php

namespace LaravelFly\Tests\Map\Unit\Server;


use LaravelFly\Tests\BaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CommonTest extends BaseTestCase
{

    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServerNoSwoole;

    /**
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServerNoSwoole(): \LaravelFly\Server\Common
    {
        if (static::$commonServerNoSwoole) return static::$commonServerNoSwoole;

        return static::$commonServerNoSwoole = new \LaravelFly\Server\Common();
    }

    function resetServerConfigAndDispatcher($server = null)
    {
        $server = $server ?: static::getCommonServerNoSwoole();
        $c = new \ReflectionProperty($server, 'options');
        $c->setAccessible(true);
        $c->setValue($server, []);

        $d = new \ReflectionProperty($server, 'dispatcher');
        $d->setAccessible(true);
        $d->setValue($server, new EventDispatcher());

    }


    function testInit()
    {
        $server = static::getCommonServerNoSwoole();

        $root = new \ReflectionProperty($server, 'root');
        $root->setAccessible(true);
        self::assertEquals(static::$laravelAppRoot, $root->getValue($server));

    }

    function testAppClass()
    {
        $server = static::getCommonServerNoSwoole();
        $this->resetServerConfigAndDispatcher($server);

        $a = new \ReflectionProperty(static::$commonServerNoSwoole, 'appClass');
        $a->setAccessible(true);

        foreach (['Map', 'Backup', 'FpmLike'] as $mode) {

            $appClass = self::process(function () use ($mode, $a, $server) {
                $server->config(['mode' => $mode, 'pre_include' => false]);
                $appClass = $a->getValue($server);
                return $appClass;
            });

            self::assertEquals("\LaravelFly\\$mode\Application", $appClass);
        }

    }

    function testDefaultOptions()
    {
        $server = static::getCommonServerNoSwoole();
        $this->resetServerConfigAndDispatcher($server);


        $server->config([]);

        $actual = $server->getConfig();
        self::assertEquals(true, isset($actual['pid_file']));

        self::assertEquals('Map', $actual['mode']);

        unset($actual['pid_file']);
        $d = static::$default;
        unset($actual['before_start_func'], $d['before_start_func']);
        self::assertEquals($d, $actual);


    }

    function testMergeOptions()
    {

        $server = static::getCommonServerNoSwoole();
        $this->resetServerConfigAndDispatcher($server);

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
        $server = static::$commonServerNoSwoole;
        $server->config(['pre_include' => false]);
        self::assertEquals(static::$laravelAppRoot . '/bootstrap/laravel-fly-9501.pid', $server->getConfig('pid_file'));

    }


    /**
     * kernelClass always be \LaravelFly\Kernel, because
     * 'App\Http\Kernel' has included in previous tests , and const LARAVELFLY_MODE not defined
     *
     * @throws \ReflectionException
     */
    function testKernalClass()
    {

        $server = static::getCommonServerNoSwoole();
        $this->resetServerConfigAndDispatcher($server);

        $k = new \ReflectionProperty($server, 'kernelClass');
        $k->setAccessible(true);

        $flyKernel = 'LaravelFly\Kernel';

        $server->config(['pre_include' => false]);
        //self::assertEquals($flyKernel, $k->getValue($server));
        self::assertEquals('App\Http\Kernel', $k->getValue($server));

    }


    function testPath()
    {
        $server = static::getCommonServerNoSwoole();
        self::assertEquals(static::$laravelAppRoot, $server->path());

        self::assertEquals(static::$laravelAppRoot . '/bootstrap/', $server->path('bootstrap/'));
    }

    function testGetMemory()
    {
        $server = static::getCommonServerNoSwoole();
        self::assertEquals(null, $server->getIntegerMemory('no-exist'));
    }
}