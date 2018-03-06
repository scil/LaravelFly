<?php
namespace LaravelFly\Tests;

use Tests\TestCase;

class BaseTestCase extends TestCase
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

