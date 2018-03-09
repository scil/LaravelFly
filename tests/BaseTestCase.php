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
    private $laravelApp;
    protected $root;

    protected function setUp()
    {

        $this->root = realpath(__DIR__ . '/../../../..');

    }

    protected function getLaravelApp()
    {
        if(!$this->laravelApp)
            $this->laravelApp = require_once $this->root . '/bootstrap/app.php';

        return $this->laravelApp;
    }

}

