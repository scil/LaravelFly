<?php

namespace LaravelFly\Tests\Unit;

use LaravelFly\Tests\BaseTestCase;

class HackFilesTestk extends BaseTestCase
{
    protected $lastCheckedVersion = '5.5.33';

    protected $map = [
        'ViewConcerns/ManagesComponents.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesComponents.php',
        'ViewConcerns/ManagesLayouts.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLayouts.php',
        'ViewConcerns/ManagesLoops.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLoops.php',
        'ViewConcerns/ManagesStacks.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesStacks.php',
        'ViewConcerns/ManagesTranslations.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesTranslations.php',
        'Application.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
        'Collection.php' => '/vendor/laravel/framework/src/Illuminate/Support/Collection.php',
        'Container.php' => '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
        'Controller.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',
        'Facade.php' => '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',
        'FileViewFinder.php' => '/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php',
        'Relation.php' => '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php',
        'Router.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
        'ServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    ];

    function testCompareFiles()
    {
        if (version_compare($this->lastCheckedVersion, $this->laravelApp->version()) >= 0) {
            return;
        }

        $backDir = __DIR__ . '/../offcial_files/';
        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        foreach ($this->map as $back => $offcial) {
            $back = $backDir . $back;
            $offcial = $this->root . $offcial;
            $cmdArguments = "$diffOPtions $back $offcial ";

            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
            if ($r !== 0) {
                echo "diff $cmdArguments\n";
            }
        }

    }
}

