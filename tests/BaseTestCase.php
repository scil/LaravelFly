<?php
namespace LaravelFly\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Class Base
 * @package LaravelFly\Tests
 *
 * why abstract? stop phpunit to use this testcase
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $laravelApp;

    protected function setUp()
    {

        $root =  realpath(__DIR__ . '/../../../..');

        $this->laravelApp = require_once $root.'/bootstrap/app.php';
    }

}

