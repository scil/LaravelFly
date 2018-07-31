<?php
/**
 * hack: Cache for view compiled path.
 *
 * add cache for view compiled file path
 *
 * overwrite the methods of the abstract class Illuminate\View\Compilers\Compiler
 */

namespace LaravelFly\Map\Illuminate\View;

class BladeCompiler_1 extends \Illuminate\View\Compilers\BladeCompiler
{
    /**
     * save view info.
     *
     * structure:
     * [
     *      path1=> compiledPath,
     *      path2=>...
     * ]
     * @var array
     */
    protected static $mapFly = [];

    public function getCompiledPath($path)
    {
        if (isset(static::$mapFly[$path])) {
            return static::$mapFly[$path];
        }

        $compiled = parent::getCompiledPath($path);

        $this->setInfo($path, $compiled);

        return $compiled;
    }

    protected function setInfo($path, $compiled)
    {
        static::$mapFly[$path] = $compiled;

        // compile if compiled not exists or old
        if (!$this->files->exists($compiled) ||
            $this->files->lastModified($compiled) <= $this->files->lastModified($path)) {
            $this->compile($path);
        }

    }

    public function isExpired($path)
    {
        if (!isset(static::$mapFly[$path])) {
            $this->setInfo($path, parent::getCompiledPath($path));
        } else if (!$this->files->exists(static::$mapFly[$path])) {
            // avoid the risk: the compiled file deleted when the swoole worker is working
            $this->compile($path);
        }
        return false;
    }
}

