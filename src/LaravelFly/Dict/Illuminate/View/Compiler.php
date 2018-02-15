<?php
/**
 * add cache for view mtime and compiled file
 *
 * this is implemented as trait
 * with the purpose to play the role of the abstract class Illuminate\View\Compilers\Compiler
 */

namespace LaravelFly\Dict\Illuminate\View;


trait Compiler
{
    protected static $map = [];

    public function getCompiledPath($path)
    {
        if (isset(static::$map[$path])) {
            return static::$map[$path][0];
        }
        return $this->saveInfo($path)[0];
    }

    protected function saveInfo($path)
    {
        $compiled = parent::getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the time is 0 ,so the compiled is expired
        // so that it can be re-compiled.
        return static::$map[$path] = [
            $compiled,
            $this->files->exists($compiled) ? $this->files->lastModified($compiled) : 0];

    }

    public function isExpired($path)
    {
        if (!isset(static::$map[$path])) {
            return true;
        }

        return $this->files->lastModified($path) >=
            static::$map[$path][1];
    }
}