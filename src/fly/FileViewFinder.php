<?php

namespace Illuminate\View;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
use LaravelFly\Map\Util\Dict;

class FileViewFinder implements ViewFinderInterface
{

    use Dict;
    protected static $arrayAttriForObj = ['paths', 'views', 'hints'];
    protected static $normalAttriForObj = [];


    protected $files;
    protected $extensions = ['blade.php', 'php', 'css'];

    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        $this->initOnWorker(true);

        static::$corDict[WORKER_COROUTINE_ID]['paths'] = $paths;
        $this->files = $files;
        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    public function find($name)
    {
        $cid = \Co::getUid();

        if (isset(static::$corDict[$cid]['views'][$name])) {
            return static::$corDict[$cid]['views'][$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return static::$corDict[$cid]['views'][$name] = $this->findNamespacedView($name, $cid);
        }

        return static::$corDict[$cid]['views'][$name] = $this->findInPaths($name, static::$corDict[$cid]['paths']);
    }

    /**
     * Get the path to a template with a named path.
     *
     * @param  string $name
     * @return string
     */
    protected function findNamespacedView($name, $cid)
    {
        list($namespace, $view) = $this->parseNamespaceSegments($name, $cid);

        return $this->findInPaths($view, static::$corDict[$cid]['hints'][$namespace]);
    }

    /**
     * Get the segments of a template with a named path.
     *
     * @param  string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseNamespaceSegments($name, $cid)
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (!isset(static::$corDict[$cid]['hints'][$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Find the given view in the list of paths.
     *
     * @param  string $name
     * @param  array $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array)$paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($viewPath = $path . '/' . $file)) {
                    return $viewPath;
                }
            }
        }
        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    /**
     * Get an array of possible view files.
     *
     * @param  string $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', '/', $name) . '.' . $extension;
        }, $this->extensions);
    }

    /**
     * Add a location to the finder.
     *
     * @param  string $location
     * @return void
     */
    public function addLocation($location)
    {
        static::$corDict[\Co::getUid()]['paths'][] = $location;
    }

    /**
     * Prepend a location to the finder.
     *
     * @param  string $location
     * @return void
     */
    public function prependLocation($location)
    {
        array_unshift(static::$corDict[\Co::getUid()]['paths'], $location);
    }

    /**
     * Add a namespace hint to the finder.
     *
     * @param  string $namespace
     * @param  string|array $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $hints = (array)$hints;

        $cid = \Co::getUid();

        if (isset(static::$corDict[$cid]['hints'][$namespace])) {
            $hints = array_merge(static::$corDict[$cid]['hints'][$namespace], $hints);
        }

        static::$corDict[$cid]['hints'][$namespace] = $hints;
    }

    /**
     * Prepend a namespace hint to the finder.
     *
     * @param  string $namespace
     * @param  string|array $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints)
    {
        $hints = (array)$hints;

        $cid = \Co::getUid();

        if (isset(static::$corDict[$cid]['hints'][$namespace])) {
            $hints = array_merge($hints, static::$corDict[$cid]['hints'][$namespace]);
        }

        static::$corDict[$cid]['hints'][$namespace] = $hints;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param  string $namespace
     * @param  string|array $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        static::$corDict[\Co::getUid()]['hints'][$namespace] = (array)$hints;
    }

    /**
     * Register an extension with the view finder.
     *
     * @param  string $extension
     * @return void
     */
    public function addExtension($extension)
    {
        if (($index = array_search($extension, $this->extensions)) !== false) {
            unset($this->extensions[$index]);
        }

        array_unshift($this->extensions, $extension);
    }

    /**
     * Returns whether or not the view name has any hint information.
     *
     * @param  string $name
     * @return bool
     */
    public function hasHintInformation($name)
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    /**
     * Flush the cache of located views.
     *
     * @return void
     */
    public function flush()
    {
        var_dump('flush', static::$corDict[\Co::getUid()]['views']);
        static::$corDict[\Co::getUid()]['views'] = [];
    }

    /**
     * Get the filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Get the active view paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return static::$corDict[\Co::getUid()]['paths'];
    }

    /**
     * Get the namespace to file path hints.
     *
     * @return array
     */
    public function getHints()
    {
        return static::$corDict[\Co::getUid()]['hints'];
    }

    /**
     * Get registered extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

}

