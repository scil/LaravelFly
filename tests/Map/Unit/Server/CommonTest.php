<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Tests\Map\MapTestCase;

class CommonTest extends MapTestCase
{
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


    function testDefaultOptions()
    {
        $this->resetServerConfigAndDispatcher();

        $server = static::$commonServer;


        $server->config([]);

        $actual = $server->getConfig();
        self::assertEquals(true, isset($actual['pid_file']));

        self::assertEquals('Map',$actual['mode']);

        unset($actual['pid_file']);
        self::assertEquals(static::$default, $actual);


    }

    function testMergeOptions()
    {
        $this->resetServerConfigAndDispatcher();

        $server = static::$commonServer;

        self::assertEquals(null, $server->getConfig('listen_ip'));
        self::assertEquals('0.0.0.0', static::$default['listen_ip']);

        // compile should be false, as compiled files has been included in the $server->config([]) above
        $server->config(['listen_ip' => '127.0.0.1', 'pre_include' => false]);
        // changed
        self::assertEquals('127.0.0.1', $server->getConfig('listen_ip'));
        // no change
        self::assertEquals(static::$default['worker_num'], $server->getConfig('worker_num'));
    }

    function testConfigPidFile(){
        static::$commonServer->config([ 'pre_include' => false]);
        self::assertEquals( static::$laravelAppRoot . '/bootstrap/laravel-fly-9501.pid',static::$commonServer->getConfig('pid_file')  );

    }

    function testAppClass(){
        $this->resetServerConfigAndDispatcher();

        $a= new \ReflectionProperty(static::$commonServer,'appClass');
        $a->setAccessible(true);

        static::$commonServer->config([ 'pre_include' => false]);
        $appClass = $a->getValue(static::$commonServer);
        self::assertEquals('\LaravelFly\Map\Application',$appClass);

        foreach (['Map','Simple','FpmLike'] as $mode){
            static::$commonServer->config(['mode'=>$mode, 'pre_include' => false]);
            $appClass = $a->getValue(static::$commonServer);
            self::assertEquals("\LaravelFly\Map\Application",$appClass);
        }

    }

    /**
     * kernelClass always be \LaravelFly\Kernel, because
     * 'App\Http\Kernel' has included in previous tests , and const LARAVELFLY_MODE not defined
     *
     * @throws \ReflectionException
     */
    function testKernalClass()
    {

        $this->resetServerConfigAndDispatcher();

        $k= new \ReflectionProperty(static::$commonServer,'kernelClass');
        $k->setAccessible(true);

        $flyKernel= 'LaravelFly\Kernel';

        static::$commonServer->config([ 'pre_include' => false]);
        //self::assertEquals($flyKernel, $k->getValue(static::$commonServer));
        self::assertEquals('App\Http\Kernel', $k->getValue(static::$commonServer));

    }


    function testPath()
    {
        self::assertEquals(static::$laravelAppRoot, static::$commonServer->path());

        self::assertEquals(static::$laravelAppRoot.'/bootstrap/', static::$commonServer->path('bootstrap/'));
    }
    function testGetMemory()
    {
        self::assertEquals(null, static::$commonServer->getMemory('no-exist'));
    }
}