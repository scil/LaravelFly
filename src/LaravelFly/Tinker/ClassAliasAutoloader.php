<?php

namespace LaravelFly\Tinker;

use Psy\Shell;
use Illuminate\Support\Str;

class ClassAliasAutoloader extends \Laravel\Tinker\ClassAliasAutoloader
{
    static $registered = false;

    /**
     * just disable $excludedAliases = collect(config('tinker.dont_alias', []));
     *
     * @param  \Psy\Shell  $shell
     * @param  string  $classMapPath
     * @return void
     */
    public function __construct(Shell $shell, $classMapPath)
    {
        $this->shell = $shell;

        $vendorPath = dirname(dirname($classMapPath));

        $classes = require $classMapPath;

        // $excludedAliases = collect(config('tinker.dont_alias', []));
        $excludedAliases = collect([]);

        foreach ($classes as $class => $path) {
            if (! Str::contains($class, '\\') || Str::startsWith($path, $vendorPath)) {
                continue;
            }

            if (! $excludedAliases->filter(function ($alias) use ($class) {
                return Str::startsWith($class, $alias);
            })->isEmpty()) {
                continue;
            }

            $name = class_basename($class);

            if (! isset($this->classes[$name])) {
                $this->classes[$name] = $class;
            }
        }
    }

    function addClasses($array)
    {
        foreach ($array as $class) {

            $name = class_basename($class);

            if (!isset($this->classes[$name])) {
                $this->classes[$name] = $class;
            }
        }

    }

    static public function register(Shell $shell, $classMapPath)
    {
        if (!static::$registered) {

            $me = parent::register($shell, $classMapPath);

            static::$registered = true;

            return $me;
        }
    }
}
