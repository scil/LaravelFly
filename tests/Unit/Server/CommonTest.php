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
        self::assertEquals(static::$laravelAppRoot, $root->getValue($server));
    }

    function testAppClass(){
        $this->resetServerConfigAndDispatcher();

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
}