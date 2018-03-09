<?php
/**
 * a file intended to replace official Filesystem, now it's not in use, because tests not show very good performance.

 * use note:  views should be compiled before any requests, otherwise something like flock should be used.

 * test swoole api :  add routes:

Route::get('swoole_file',function(){
    return file_get_contents($path);
});
Route::get('swoole_file',function(){
    $path= storage_path( 'free/article/Zi-ZhongYun-why-intellectuals-changedbody.html');
    $handle = fopen($path, "r");
    $r = \Swoole\Dict::fread($handle);
    fclose($handle);
    return $r;
});
 */

namespace LaravelFly\Hash\Illuminate;

use ErrorException;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Swoole\Coroutine as Co;

class Filesystem extends \Illuminate\Filesystem\Filesystem
{

    protected function swooleGet($path)
    {
        ;
    }

    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            if ($lock) {
                return $this->sharedGet($path);
            } else {
                $handle = fopen($path, "r");
                $r = Co::fread($handle);
                fclose($handle);
                return $r;

            }
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = Co::fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }


    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $path;
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    public function requireOnce($file)
    {
        require_once $file;
    }

    public function put($path, $contents, $lock = false)
    {
        if ($lock)
            return file_put_contents($path, $contents, LOCK_EX);
        else {
            $handle = fopen($path, "w");
            $r=Co::fwrite($handle,$contents);
            fclose($handle);
            return $r;
        }
    }

    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);

        $handle = fopen($path, "a");
        $r=Co::fwrite($handle,$contents);
        fclose($handle);
        return $r;

    }

}

