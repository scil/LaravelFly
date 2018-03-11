<?php
/**
 * add cache for view mtime and compiled file
 *
 * this is implemented as trait
 * with the purpose to play the role of the abstract class Illuminate\View\Compilers\Compiler
 */

namespace LaravelFly\Map\Illuminate\View;


trait Compiler
{
    /**
     * save view info.
     *
     * structure:
     * [
     *      path1=>[compiledPath, compiledTime],
     *      path2=>...
     * ]
     * @var array
     */
    protected static $mapFly = [];

    public function getCompiledPath($path)
    {
        if (isset(static::$mapFly[$path])) {
            return static::$mapFly[$path][0];
        }
        return $this->saveInfo($path)[0];
    }

    protected function saveInfo($path)
    {
        $compiled = parent::getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the time is 0 ,so the compiled is expired
        // so that it can be re-compiled.
        return static::$mapFly[$path] = [
            $compiled,
            $this->files->exists($compiled) ? $this->files->lastModified($compiled) : 0];

    }

    public function isExpired($path)
    {
        if (!isset(static::$mapFly[$path])) {
            return true;
        }

        return $this->files->lastModified($path) >=
            static::$mapFly[$path][1];
    }
}