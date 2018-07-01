<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Tests\Map\MapTestCase as Base;
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

    }


}