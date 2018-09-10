<?php

namespace LaravelFly\Map\Illuminate\Translation;

class FileLoader extends \Illuminate\Translation\FileLoader
{

    public function addJsonPath($path)
    {
        if (!\in_array($path, $this->jsonPaths, true))
            $this->jsonPaths[] = $path;
    }

}