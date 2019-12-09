<?php

/**
 * Prepare:
 *   composer create-project --prefer-dist laravel/laravel blog_for_test
 *   cd blog_for_test
 *   composer require scil/laravel-fly
 *   add to composer.json
 *         "autoload-dev": { "psr-4": { "LaravelFly\\Tests\\": "vendor/scil/laravel-fly/tests/" }  }
 *   edit phpunit.xml
 *          LARAVEL_PROJECT_ROOT
 *   xml=vendor/scil/laravel-fly/phpunit.xml.dist
 */

/**
 * One
 *
 * 1. scil/laravel-fly-files
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit only_fly
 *
 * 2. Mode Map
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Map_Process
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Map_No_Process_Used
 *
 *
 *  3. Mode Backup
 * vendor/bin/phpunit  --stop-on-failure -c $xml --testsuit LaravelFly_Backup
 *
 *
 */

 /**
 * Two
  * use Laravel built-in tests
  *
 ** prepare:
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

    /**
     * @var string
     */
    static protected $laravelVersionAppRoot = '';

    static $flyDir;

    static $backOfficalDir;

    static $workingRoot;

    /**
     * @var \Swoole\Channel
     */
    static protected $chan;

    static protected $chan_fail_file = '/tmp/phpunit_zc_chan';


    static function setUpBeforeClass():void
    {

        $r = static::$laravelAppRoot;


        if (!is_dir($r . '/app')) {
            exit("[NOTE] FORCE setting \$laravelAppRoot= $r,please make sure laravelfly code or its soft link is in laravel_app_root/vendor/scil/\n");
        }

        static::$workingRoot = $r;

        $d = static::processGetArray(function () {
            return include static::$laravelAppRoot . '/vendor/scil/laravel-fly/config/laravelfly-server-config.example.php';
        });

        static::$default = array_merge([
            'mode' => 'Map',
            'conf' => null, // server config file
        ], $d);

        static::initFlyfiles();
    }

    static function initFlyfiles()
    {

        $files_package_root = FLY_ROOT . '/../laravel-fly-files/';
        static::$flyDir = $files_package_root . '/src/';
        static::$backOfficalDir = $files_package_root . '/offcial_files/';

        if (env('LARAVEL_VERSION_PROJECT_ROOT'))
            static::$laravelVersionAppRoot = env('use Astrotomic\Translatable\Translatable;');
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

        $difNum = 0;

        foreach ($map as $back => $offcial) {
            $back = realpath(static::$backOfficalDir . $back);
            $full_offcial = static::$laravelVersionAppRoot . $offcial;

            if(!is_file($full_offcial))
                $full_offcial = static::$laravelAppRoot.$offcial;

            $cmdArguments = "$diffOPtions $back $full_offcial ";

            unset($a);
            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
//            echo "\n\n[CMD] diff $cmdArguments\n\n";
//            print_r($a);
            if ($r !== 0) {
                $difNum ++;
                echo "\n\n[CMD] diff $cmdArguments\n\n";
                system("diff  $cmdArguments");
            }
        }

        self::assertEquals(0, $difNum);

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
     * run $funcInNewProcess in a child process and return its result as string
     *
     * @param $funcInNewProcess
     * @param int $waittime
     * @return string|int|boolean    it will json_encode array
     */
    static function _process($funcInNewProcess, $func, $waittime = 1): string
    {

        static $chan = null;

        if (is_file(static::$chan_fail_file))
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

            if (!$c && is_file(static::$chan_fail_file))
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


    /**
     * make(not start jet) a server in child process, request some urls in parent process and return responses
     * @param $constances
     * @param $options
     * @param $urls
     * @param null $serverFunc
     * @param int $wait
     * @return string
     */
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

    /**
     * based on static::request, not only making a server, but also starting it
     * @param $workerreadyCallback
     * @param $urls
     * @param $expect
     * @param int $wait
     */
    function assertResponse($workerreadyCallback, $urls, $expect, $wait = 3)
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

            function (HttpServer $server) use ($workerreadyCallback) {

                $server->getDispatcher()->addListener('worker.ready', function () use ($workerreadyCallback) {
                    $workerreadyCallback();
                });

                $server->start();

            }, $wait);

        self::assertEquals(implode("\n", $expect), $r);
    }

    /**
     * register routes when event 'worker.ready'
     * @param array $routes
     * @param array $urls
     * @param array $expect
     * @param null $funcOnWork
     * @param int $wait
     */
    function assertResponsePassingRoutes(array $routes, array $urls, array $expect, $funcOnWork = null, $wait = 3)
    {
        assert(count($urls) === count($expect));

        $callback = function () use ($routes, $funcOnWork) {

            foreach ($routes as list($method, $url, $func)) {

                assert(in_array($method, ['get', 'post', 'put', 'delete']));
                assert(is_string($url));
                assert(is_callable($func));

                \Route::$method($url, $func);
            }

            if (is_callable($funcOnWork)) $funcOnWork();
        };

        $this->assertResponse($callback, $urls, $expect, $wait);

    }

    function getLastLog()
    {
        $this->assertEquals('single',env('LOG_CHANNEL'),'please ensure LOG_CHANNEL to single');

        $log = file(LARAVEL_APP_ROOT . '/storage/logs/laravel.log');
        $line = $log[count($log) - 2];
        $line = substr($line, strpos($line, 'INFO') + 6);
        $line = trim($line);

        return $line;
    }

}

