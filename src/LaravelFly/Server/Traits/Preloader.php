<?php

/**
 * from:
 * https://github.com/laravel/framework/blob/101cb80750cb81fe26cd38686b8464a7e943330d/src/Illuminate/Foundation/Console/OptimizeCommand.php
 *
 * [Laravel 5.6 Will Remove the Artisan Optimize Command](https://laravel-news.com/laravel-5-6-removes-artisan-optimize)
 * because of PHP op-code caching
 * but LaravelFly uses it,
 * because LaravelFly uses `opcache_reset()` in each worker
 */

namespace LaravelFly\Server\Traits;

use ClassPreloader\Factory;
use ClassPreloader\Exceptions\VisitorExceptionInterface;

Trait Preloader
{

    protected function preInclude()
    {
        $this->echo("include pre-files");

        foreach ($this->getClassFiles() as $f) {
            try {
                $file = realpath($f);
                if (!is_string($file) || empty($file)) {
                    $this->echo(".. Invalid pre-include filename $f provided.",'WARN');
                    continue;
                }

                if (!is_readable($file)) {
                    $this->echo(".. Cannot open pre-include $f for reading.",'WARN');
                    continue;
                }

                include $file;
            } catch (\Exception $e) {
                //
            }
        }
    }

    /**
     * Get the classes that should be combined and compiled.
     *
     * @return array
     */
    protected function getClassFiles()
    {
        $core = require __DIR__ . '/preloader_config_onlymapmode.php';

        // Map mode has loaded fly files and related files, so non-Map mode can load more files now
        if ($this->getConfig('mode') !== 'Map') {
            $core = $core + (require __DIR__ . '/preloader_config_more.php');
            if (!$this->getConfig('log_cache')) {
                $core[] = $this->root . '/vendor/monolog/monolog/src/Monolog/Handler/HandlerInterface.php';
                $core[] = $this->root . '/vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php';
                $core[] = $this->root . '/vendor/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php';
                $core[] = $this->root . '/vendor/monolog/monolog/src/Monolog/Handler/StreamHandler.php';
            }
        }

        return $core + ( $this->getConfig('pre_files') ?: []);
    }


}

