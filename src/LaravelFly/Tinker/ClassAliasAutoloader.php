<?php

namespace LaravelFly\Tinker;

use Psy\Shell;
use Illuminate\Support\Str;

class ClassAliasAutoloader extends \Laravel\Tinker\ClassAliasAutoloader
{
    static $registered = false;

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
