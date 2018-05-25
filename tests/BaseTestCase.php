<?php


/**
 *
 * cdzc && vendor/bin/phpunit  --stop-on-failure  -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Unit
 *
 * Map
 * cdzc && vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Unit
 * cdzc && vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Unit2
 * cdzc && vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Feature
 * cdzc && vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Feature2
 * cdzc && vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_LaravelTests
 *
 */

namespace LaravelFly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
    static private $laravelApp;

    /**
     * @var EventDispatcher
     */
    static protected $dispatcher;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    static protected $server;

    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServer;

    // get default server options
    static $default = [];

    /**
     * @var string
     */
    static protected $workingRoot;
    static protected $laravelAppRoot;

    static function setUpBeforeClass()
    {

        if (!AS_ROOT) {
            static::$laravelAppRoot = static::$workingRoot = realpath(__DIR__ . '/../../../..');
            return;
        }

        static::$workingRoot = realpath(__DIR__ . '/..');
        $r = static::$laravelAppRoot = realpath(static::$workingRoot . '/../../..');

        if (!is_dir($r . '/app')) {
            exit("[NOTE] FORCE setting \$laravelAppRoot= $r,please make sure laravelfly code or its soft link is in laravel_app_root/vendor/scil/\n");
        }
    }

    /**
     * Get laravel official App instance, but instance of any of Laravelfly Applications
     *
     * @return \Illuminate\Foundation\Application
     */
    static protected function getLaravelApp()
    {
        if (!self::$laravelApp)
            self::$laravelApp = require static::$laravelAppRoot . '/bootstrap/app.php';

        return self::$laravelApp;
    }

    static protected function makeCommonServer()
    {
        static::$commonServer = new \LaravelFly\Server\Common();

        // get default server options
        $d = new \ReflectionProperty(static::$commonServer, 'defaultOptions');
        $d->setAccessible(true);
        static::$default = $d->getValue(static::$commonServer);
    }

    static protected function makeNewServer($constances = [], $options = [], $config_file = __DIR__ . '/../config/laravelfly-server-config.example.php')
    {
        foreach ($constances as $name => $val) {
            if (!defined($name))
                define($name, $val);
        }

        $file_options = require $config_file;

        $options = array_merge($file_options, $options);

        if (!isset($options['compile']))
            $options['compile'] = false;

        $fly = \LaravelFly\Fly::init($options);

        static::$dispatcher = $fly->getDispatcher();

        return static::$server = $fly->getServer();
    }

    /**
     * @return \LaravelFly\Server\ServerInterface
     */
    public static function getServer(): \LaravelFly\Server\ServerInterface
    {
        return self::$server;
    }

    /**
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServer(): \LaravelFly\Server\Common
    {
        return self::$commonServer;
    }

    function resetServerConfigAndDispatcher($server = null)
    {
        $server = $server ?: static::$commonServer;
        $c = new \ReflectionProperty($server, 'options');
        $c->setAccessible(true);
        $c->setValue($server, []);

        $d = new \ReflectionProperty($server, 'dispatcher');
        $d->setAccessible(true);
        $d->setValue($server, new EventDispatcher());

    }

    /**
     * to create swoole server in phpunit, use this instead of server::setSwooleForServer
     *
     * @param $options
     * @return \swoole_http_server
     * @throws \ReflectionException
     *
     * server::setSwooleForServer may produce error:
     *  Fatal error: Swoole\Server::__construct(): eventLoop has already been created. unable to create swoole_server.
     */
    function setSwooleForServer($options): \swoole_http_server
    {
        $options = array_merge(self::$default, $options);

        $swoole = new \swoole_http_server($options['listen_ip'], $options['listen_port']);
        $swoole->set($options);

        $s = new \ReflectionProperty(static::$commonServer, 'swoole');
        $s->setAccessible(true);
        $s->setValue(static::$commonServer, $swoole);


        $swoole->fly = static::$commonServer;
        static::$commonServer->setListeners();

        return $swoole;
    }
}

