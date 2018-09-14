<?php


/**
 * One
 **first:
 **   cd laravel_project_root
 **   $xml = vendor/scil/laravel-fly/phpunit.xml.dist
 *
 ** Mode Map
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Map_Process
 *
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Map_Other
 *
 *
 ** Mode Backup
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Backup
 *
 *
 * Two
 **first:
 **   cd laravel_fly_root
 **   git clone -b 5.6 https://github.com/laravel/framework.git /vagrant/www/zc/vendor/scil/laravel-fly-local/vendor/laravel/framework
 **   composer update
 * vendor/bin/phpunit  --stop-on-failure -c phpunit.xml.dist --testsuit LaravelFly_Map_LaravelTests
 *
 ** example for debugging with gdb:
 * gdb ~/php/7.1.14root/bin/php       // this php is a debug versioin, see D:\vagrant\ansible\files\scripts\php-debug\
 * r  vendor/bin/phpunit  --stop-on-failure -c phpunit.xml.dist --testsuit LaravelFly_Map_LaravelTests
 *
 */

namespace LaravelFly\Tests;

use LaravelFly\Server\Common;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use LaravelFly\Server\HttpServer;


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
    static protected $laravelAppRoot = LARAVEL_APP_ROOT;

    static $flyDir;

    static $backOfficalDir;

    /**
     * @var \Swoole\Channel
     */
    static protected $chan;

    static protected $chan_fail_file = '/tmp/phpunit_zc_chan';


    static function setUpBeforeClass()
    {

        $r = static::$laravelAppRoot;


        if (!is_dir($r . '/app')) {
            exit("[NOTE] FORCE setting \$laravelAppRoot= $r,please make sure laravelfly code or its soft link is in laravel_app_root/vendor/scil/\n");
        }

        static::$flyDir = FLY_ROOT . '/src/fly/';
        static::$backOfficalDir = FLY_ROOT . '/tests/offcial_files/';

        $d = static::processGetArray(function () {
            return include static::$laravelAppRoot . '/vendor/scil/laravel-fly/config/laravelfly-server-config.example.php';
        });

        static::$default = array_merge([
            'mode' => 'Map',
            'conf' => null, // server config file
        ], $d);
    }

    /**
     * @param array $constances
     * @param array $options
     * @param string $config_file
     * @param bool $swoole
     * @return \LaravelFly\Server\HttpServer|\LaravelFly\Server\ServerInterface
     */
    static protected function makeNewFlyServer($constances = [], $options = [], $config_file = DEFAULT_SERVER_CONFIG_FILE)
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

        // why @ ? For the errors like : Constant LARAVELFLY_MODE already defined
        $file_options = @ include $config_file;

        $options = array_merge($file_options, $options);

        if (empty($options['application'])) {
            if ($options['mode'])
                $options['application'] = '\LaravelFly\\' . $options['mode'] . '\Application';
            elseif (defined(('LARAVELFLY_MODE')))
                $options['application'] = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';
        }

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
            $back = static::$backOfficalDir . $back;
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

    static function processGetArray($func, $waittime = 1): array
    {
//        return self::process($func, $waittime);
        return json_decode(self::process($func, $waittime), true);
    }

    static function process($funcInNewProcess, $waittime = 1): string
    {
        return static::_process($funcInNewProcess, null, $waittime);
    }


    /**
     * @param $funcInNewProcess
     * @param int $waittime
     * @return string|int|boolean    it will json_encode array
     */
    static function _process($funcInNewProcess, $func, $waittime = 1): string
    {

        static $chan = null;

        if(is_file(static::$chan_fail_file))
            @unlink(static::$chan_fail_file);

        if (null === $chan)
            $chan = new \Swoole\Channel(1024 * 256);

        $pm = new \ProcessManager;


        $pm->childFunc = function () use ($funcInNewProcess, $pm, $chan) {
            $r = $funcInNewProcess();
            if (is_array($r)) {
                $r = json_encode($r);
            }
            // file_put_contents('/vagrant/www/zc/child_chan.tmp', $r);

            // todo 为什么parent方面会pop不到数据？
            // if($chan->push($r)===false)
                file_put_contents(static::$chan_fail_file, $r);

            $pm->wakeup();

        };
        // server can not be made in parentFunc,because parentFunc run in current process
        $pm->parentFunc = function ($pid) use ($func, $chan, $waittime) {
            $c = $chan->pop();

            if(!$c && is_file(static::$chan_fail_file))
                $c = file_get_contents(static::$chan_fail_file);


            // file_put_contents('/vagrant/www/zc/parent_chan.tmp', $c);
            echo $c;

            if (is_callable($func))
                echo $func();

            sleep($waittime);

            \swoole_process::kill($pid);
        };
        $pm->childFirst();

        ob_start();
        $pm->run();
        $r = ob_get_clean();
        // file_put_contents('/vagrant/www/zc/process.tmp', $r);
        return $r;
    }

    static function createFlyServerInProcess($constances, $options, $serverFunc, $wait = 0): string
    {
        $r = self::process(function () use ($constances, $options, $serverFunc) {
            $server = self::makeNewFlyServer($constances, $options);
            $r = $serverFunc($server);
            return $r;
        }, $wait);

         // file_put_contents('/vagrant/www/zc/flyserver.tmp',$r);

        return $r;
    }


    static function request($constances, $options, $urls, $serverFunc = null, $wait = 0): string
    {
        return self::_process(function () use ($constances, $options, $serverFunc) {

            $server = self::makeNewFlyServer($constances, $options);

            if (is_callable($serverFunc))
                $r = $serverFunc($server);

            return '';

        }, function () use ($urls) {
            $r = curl_concurrency($urls);

            return implode("\n", $r);

        }, $wait);

    }


    const testPort = '9503';

    const testBaseUrl = '/laravelfly-test/';

    const testCurlBaseUrl = '127.0.0.1:' . (self::testPort) . self::testBaseUrl;

    function requestAndTestAfterOnWorker($callback, $urls, $expect)
    {

        $constances = [
        ];

        $options = [
            'worker_num' => 1,
            'mode' => 'Map',
            'listen_port' => self::testPort,
            'daemonize' => false,
            'pre_include' => false,
        ];

        $r = self::request($constances, $options, $urls,

            function (HttpServer $server) use ($callback) {

                $server->getDispatcher()->addListener('worker.ready', function () use ($callback) {
                    $callback();
                });

                $server->start();

            }, 3);

        self::assertEquals(implode("\n", $expect), $r);
    }

    function requestAndTestAfterRoute($routes, $urls, $expect, $funcOnWork=null)
    {

        $callback = function () use ($routes, $funcOnWork) {

            foreach ($routes as list($method, $url, $func)) {
                \Route::$method($url, $func);
            }

            if(is_callable($funcOnWork)) $funcOnWork();
        };

        $this->requestAndTestAfterOnWorker($callback, $urls, $expect);

    }
}

