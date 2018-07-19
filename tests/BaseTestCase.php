<?php


/**
 ** first:
 ** cd laravel_project_root
 *
 ** Mode simple
 * vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Simple_Unit
 *
 ** Mode Map
 * vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Unit
 * vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Unit2
 *
 * vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_Feature
 *
 * vendor/bin/phpunit  --stop-on-failure -c vendor/scil/laravel-fly/phpunit.xml.dist --testsuit LaravelFly_Map_LaravelTests
 *
 ** example for debugging with gdb:
 * gdb ~/php/7.1.14root/bin/php       // this php is a debug versioin, see D:\vagrant\ansible\files\scripts\php-debug\
 * r  ../../bin/phpunit  --stop-on-failure -c phpunit.xml.dist --testsuit LaravelFly_Map_LaravelTests
 *
 */

namespace LaravelFly\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Base
 * @package LaravelFly\Tests
 *
 * why abstract? stop phpunit to use this testcase
 */
abstract class BaseTestCase extends TestCase
{
    use DirTest;

    /**
     * @var EventDispatcher
     */
    static protected $dispatcher;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    static protected $flyServer;


    // get default server options
    static $default = [];

    /**
     * @var string
     */
    static protected $workingRoot;
    static protected $laravelAppRoot;

    protected $flyDir = __DIR__ . '/../src/fly/';

    protected $backOfficalDir = __DIR__ . '/offcial_files/';

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


        $d = static::processGetArray(function () {
            return include static::$laravelAppRoot . '/vendor/scil/laravel-fly/config/laravelfly-server-config.example.php';
        });

        static::$default = array_merge([
            'mode' => 'Map',
            'conf' => null, // server config file
            'colorize' => true,
        ], $d);
    }

    /**
     * @param array $constances
     * @param array $options
     * @param string $config_file
     * @param bool $swoole
     * @return \LaravelFly\Server\HttpServer|\LaravelFly\Server\ServerInterface
     */
    static protected function makeNewFlyServer($constances = [], $options = [], $config_file = __DIR__ . '/../config/laravelfly-server-config.example.php')
    {
        static $step = 0;

        foreach ($constances as $name => $val) {
            if (!defined($name))
                define($name, $val);
        }

        $options['colorize'] = false;

        if (!isset($options['pre_include']))
            $options['pre_include'] = false;
        if (!isset($options['listen_port'])) {
            $options['listen_port'] = 9022 + $step;
            ++$step;
        }

        $file_options = require $config_file;

        $options = array_merge($file_options, $options);

        $flyServer = \LaravelFly\Fly::init($options, null);

        static::$dispatcher = $flyServer->getDispatcher();

        return static::$flyServer = $flyServer;
    }

    /**
     * @return \LaravelFly\Server\ServerInterface
     */
    public static function getFlyServer(): \LaravelFly\Server\ServerInterface
    {
        return self::$flyServer;
    }

    function compareFilesContent($map)
    {

        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        $same = true;

        foreach ($map as $back => $offcial) {
            $back = $this->backOfficalDir . $back;
            $offcial = static::$laravelAppRoot . $offcial;
            $cmdArguments = "$diffOPtions $back $offcial ";

            unset($a);
            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
//            echo "\n\n[CMD] diff $cmdArguments\n\n";
//            print_r($a);
            if ($r !== 0) {
                $same = false;
                echo "\n\n[CMD] diff $cmdArguments\n\n";
                system("diff  $cmdArguments");
            }
        }

        self::assertEquals(true, $same);

    }

    static function processGetArray($func, $waittime=1)
    {
        return json_decode(self::process($func, $waittime), true);
    }

    /**
     * @param $func
     * @param int $waittime
     * @return string|int|boolean    it will json_encode array
     */
    static function process($func, $waittime = 1)
    {

        require_once __DIR__ . "/swoole_src_tests/include/swoole.inc";
        require_once __DIR__ . "/swoole_src_tests/include/lib/curl_concurrency.php";

        ob_start();
        $pm = new \ProcessManager;

        $chan = new \Swoole\Channel(1024 * 256);

        $pm->childFunc = function () use ($func, $pm, $chan) {
            $r = $func();
            if (is_array($r)) {
                $chan->push(json_encode($r));
            } else {
                $chan->push($r);
            }
            $pm->wakeup();

        };
        // server can not be made in parentFunc,because parentFunc run in current process
        $pm->parentFunc = function ($pid) use ($chan, $waittime) {
            sleep($waittime);
            echo $chan->pop();
            \swoole_process::kill($pid);
        };
        $pm->childFirst();
        $pm->run();

        return ob_get_clean();
    }

    static function createFlyServerInProcess($constances, $options, $func, $wait = 0)
    {
        return self::process(function () use ($constances, $options, $func) {
            $server = self::makeNewFlyServer($constances, $options);
            $r = $func($server);
//            var_dump("result from func(server):",$r);
            return $r;
        }, $wait);

    }
}

