<?php
namespace LaravelFly\Map\Illuminate\Translation;

use LaravelFly\Map\Util\Dict;

class FileLoader extends \Illuminate\Translation\FileLoader
{
    use Dict;

    protected static $arrayAttriForObj = ['jsonPaths'];

    public function addJsonPath($path)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['jsonPaths'][] = $path;
    }

    protected function loadJsonPaths($locale)
    {
        // hack
        return collect(array_merge(static::$corDict[\Swoole\Coroutine::getuid()]['jsonPaths'], [$this->path]))
            ->reduce(function ($output, $path) use ($locale) {
                if ($this->files->exists($full = "{$path}/{$locale}.json")) {
                    $decoded = json_decode($this->files->get($full), true);

                    if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("Translation file [{$full}] contains an invalid JSON structure.");
                    }

                    $output = array_merge($output, $decoded);
                }

                return $output;
            }, []);
    }
}