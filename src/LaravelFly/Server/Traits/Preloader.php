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
    protected $preloadFile;

    protected function getCachedCompilePath()
    {
        if ($this->preloadFile === null)
            $this->preloadFile = $this->path('bootstrap/cache/laravelfly_preload.php');

        return $this->preloadFile;
    }

    public function loadCachedCompileFile()
    {
        if (!is_file($this->getCachedCompilePath()) ||
            filemtime($this->getCachedCompilePath()) < filemtime($this->path('composer.json'))) {
            $this->compileClasses();
        }

        //require $this->preloadFile;
    }

    /**
     * Generate the compiled class file.
     *
     * @return void
     */
    protected function compileClasses()
    {
        $preloader = (new Factory)->create(['skip' => true]);
        $handle = $preloader->prepareOutput($this->getCachedCompilePath());
        foreach ($this->getClassFiles() as $file) {
            try {
                fwrite($handle, $preloader->getCode($file, false) . "\n");
            } catch (VisitorExceptionInterface $e) {
                //
            }
        }
        fclose($handle);
    }

    /**
     * Get the classes that should be combined and compiled.
     *
     * @return array
     */
    protected function getClassFiles()
    {

        $autoload_classmap_file= $this->root . '/vendor/composer/autoload_classmap.php';

        if(empty($this->options['preload_classes']) || !is_file($autoload_classmap_file)) return [];

        $preload=[];
        $forbid=[];
        foreach ( $this->options['preload_classes'] as $k=>$v){
            if(is_string($k)){
                $preload[]=$k;
                $forbid[]= array_merge($forbid,$v);
            }else{
                $preload[]=$v;
            }
        }
        print_r($preload);
        print_r($forbid);

        $classFiles=[];
        $swooleClassFiles=[];

        foreach (require $autoload_classmap_file as $class=>$path){
            foreach ($forbid as $f){
                if(substr($class, 0, strlen($f)) === $f){
                    continue;
                }
            }

            if(substr($class,0,strlen('LaravelFly'))==='LaravelFly'){
                include $class;
                continue;
            }

            foreach ($preload as $p){
                if(substr($class, 0, strlen($p)) === $p){
                    $classFiles[]=$path;
                }
            }

        }

        return $classFiles;
    }

}

