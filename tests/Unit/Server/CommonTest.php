<?php

namespace LaravelFly\Tests\Unit\Server;

//use LaravelFly\Tests\BaseTestCase;

class CommonTest extends CommonServerTestCase
{

    function testInit()
    {
        $server = static::$commonServer;

        $root = new \ReflectionProperty($server, 'root');
        $root->setAccessible(true);
        self::assertEquals(static::$root, $root->getValue($server));
    }

    function testDefaultOptions()
    {
        $this->resetConfigAndResetDispatcher();

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
        $this->resetConfigAndResetDispatcher();

        $server = static::$commonServer;

        self::assertEquals(null, $server->getConfig('listen_ip'));
        self::assertEquals('0.0.0.0', static::$default['listen_ip']);

        // compile should be false, as compiled files has been included in the $server->config([]) above
        $server->config(['listen_ip' => '127.0.0.1', 'compile' => false]);
        // changed
        self::assertEquals('127.0.0.1', $server->getConfig('listen_ip'));
        // no change
        self::assertEquals(static::$default['worker_num'], $server->getConfig('worker_num'));
    }

    function testConfigEvent()
    {
        $dispatcher = static::$commonServer->getDispatcher();

        $dispatcher->addListener('server.config', function ($event) {
            /*
             * if use `$event['options']['worker_num'] =1`,   error:
             *       Indirect modification of overloaded element
             */
            $options = $event['options'];
            $options['worker_num'] += 1;
            $event['options'] = $options;
        });

        static::$commonServer->config(['worker_num' => 3, 'compile' => false]);
        // event handler can change options
        self::assertEquals(4, static::$commonServer->getConfig('worker_num'));

    }

    function testConfigPidFile(){
        static::$commonServer->config([ 'compile' => false]);
        self::assertEquals( static::$root . '/bootstrap/laravel-fly-9501.pid',static::$commonServer->getConfig('pid_file')  );

    }

    function testAppClass(){
        $this->resetConfigAndResetDispatcher();

        $a= new \ReflectionProperty(static::$commonServer,'appClass');
        $a->setAccessible(true);

        static::$commonServer->config([ 'compile' => false]);
        $appClass = $a->getValue(static::$commonServer);
        self::assertEquals('\LaravelFly\Map\Application',$appClass);

        foreach (['Map','Simple','FpmLike'] as $mode){
            static::$commonServer->config(['mode'=>$mode, 'compile' => false]);
            $appClass = $a->getValue(static::$commonServer);
            self::assertEquals("\LaravelFly\\$mode\Application",$appClass);
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

        $this->resetConfigAndResetDispatcher();

        $k= new \ReflectionProperty(static::$commonServer,'kernelClass');
        $k->setAccessible(true);

        $flyKernel= 'LaravelFly\Kernel';

        static::$commonServer->config([ 'compile' => false]);
        self::assertEquals($flyKernel, $k->getValue(static::$commonServer));

        $this->resetConfigAndResetDispatcher();
        define('LARAVELFLY_MODE','Simple');
        static::$commonServer->config([ 'compile' => false]);
        self::assertEquals($flyKernel, $k->getValue(static::$commonServer));
    }

    function testPath()
    {
        self::assertEquals(static::$root, static::$commonServer->path());

        self::assertEquals(static::$root.'/bootstrap/', static::$commonServer->path('bootstrap/'));
    }
    function testGetMemory()
    {
        self::assertEquals(null, static::$commonServer->getMemory('no-exist'));
    }
}