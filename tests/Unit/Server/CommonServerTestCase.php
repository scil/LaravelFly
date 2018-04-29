<?php

namespace LaravelFly\Tests\Unit\Server;

use LaravelFly\Tests\BaseTestCase as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class CommonServerTestCase extends Base
{

    /**
     * create a server and get default server options
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        parent::makeCommonServer();
        if(!defined('WORKER_COROUTINE_ID')){
            define('WORKER_COROUTINE_ID',1);
        }

    }


}