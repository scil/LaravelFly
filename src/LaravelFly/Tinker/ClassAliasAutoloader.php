<?php

namespace LaravelFly\Tinker;

use Psy\Shell;
use Illuminate\Support\Str;

class ClassAliasAutoloader extends \Laravel\Tinker\ClassAliasAutoloader
{
    function addClasses($array)
    {
        foreach ($array as $class){

            $name = class_basename($class);

            if (! isset($this->classes[$name])) {
                $this->classes[$name] = $class;
            }
        }

    }
}
