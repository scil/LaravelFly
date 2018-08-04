<?php

namespace LaravelFly\Tests;

use LaravelFly\Server\Common;
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
        $r = ob_get_clean();
        // file_put_contents('/vagrant/test_ob_abc',$r);
        return $r;
    }

    function dirTestInProcess($init_func, $testsDir)
    {
        return self::process(function () use ($init_func, $testsDir) {
            $init_func();
            return $this->dirTestInOb($testsDir);
        });
    }

    protected function _includeFlyFiles($makeApp = false)
    {
        $constances = [
            'WORKER_COROUTINE_ID' => -1,
            'LARAVELFLY_MODE' => 'Map',
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

        if ($makeApp) {
            $commonServer = new \LaravelFly\Server\Common();
            $commonServer->_makeLaravelApp();
            $appR = new \ReflectionProperty(Common::class,'app');
            $appR->setAccessible(true);
            $app = $appR->getValue($commonServer);
            return $app;
        }

    }

    // first include fly files, then do test
    function dirTestOnFlyFiles($testsDir, $makeApp = false)
    {
        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        $init = function () use ($makeApp) {
            $this->_includeFlyFiles($makeApp);
        };

        return $this->dirTestInProcess($init, $testsDir);

    }

    function dirTestOnFlyServer($testsDir)
    {

        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        $init = function () {

            static::makeNewFlyServer([], [
                'worker_num' => 1,
                'listen_port' => 9601,
                // EARLY
                'early_laravel' => true
            ]);
        };

        return $this->dirTestInProcess($init , $testsDir);


    }
}