<?php


namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Tests\Map\MapTestCase as Base;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\Test;


class TestCase extends Base
{
    static $laravelTestsDir = __DIR__ .'/../../../vendor/laravel/framework/tests';

   function testView()
   {
       $testRunner = new TestRunner();
       $test = $testRunner->getTest(static::$laravelTestsDir .'/View', '', 'Test.php');
       $testRunner->dorun($test);
   }


}