<?php

namespace LaravelFly\Tests;

use PHPUnit\TextUI\TestRunner;

trait DirTest
{

    function dirTestInOb($testsDir)
    {

        ob_start();
        $testRunner = new TestRunner();
        $suit = $testRunner->getTest($testsDir, '', 'Test.php');
        try {
            $testRunner->doRun($suit, ['stopOnError' => false], false);
        } catch (\ReflectionException $e) {
            echo $e->getMessage();
        }
        $r= ob_get_clean();
        file_put_contents('/vagrant/llll/abc',$r);
        return $r;
    }

    function dirTestInProcess($init_func, $testsDir)
    {
        return self::process(function () use ($init_func, $testsDir) {
            $init_func();
            return $this->dirTestInOb($testsDir);
        });
    }

    // first include fly files, then do test
    function dirTestOnFlyFiles($testsDir, $app = false)
    {
        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        return $this->dirTestInProcess(function () use ($app) {

            $constances = [
                'WORKER_COROUTINE_ID' => -1,
                'LARAVELFLY_SERVICES' => [
                    'config' => false,
                    'kernel' => false,
                    'hash' => false,
                    'view.finder' => false,

                ]
            ];
            foreach ($constances as $name => $val) {
                define($name, $val);
            }

            $options = ['mode' => 'Map', 'log_cache' => true,];

            \LaravelFly\Server\Common::includeFlyFiles($options);

            if ($app) {
                $commonServer = new \LaravelFly\Server\Common();
                $commonServer->_makeLaravelApp();
            }

        }, $testsDir);

    }

    function dirTestOnFlyServer($testsDir)
    {

        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        return $this->dirTestInProcess(function ()  {

            static::makeNewFlyServer(['LARAVELFLY_MODE' => 'Map'], [
                'worker_num' => 1,
                'listen_port' => 9601,
                // EARLY
                'early_laravel' => true
            ]);
        }, $testsDir);


    }
}