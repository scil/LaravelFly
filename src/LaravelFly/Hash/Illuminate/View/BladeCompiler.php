<?php
/**
 * add cache for view mtime and compiled file
 *
 */

namespace LaravelFly\Hash\Illuminate\View;

class BladeCompiler extends \Illuminate\View\Compilers\BladeCompiler
{
    use Compiler;

    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (!is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            $compiled = $this->getCompiledPath($this->getPath());

            if (false !== $this->files->put($compiled, $contents)) {
                static::$mapFly[$path][1] = $this->files->lastModified($compiled);
            }

        }


    }
}

