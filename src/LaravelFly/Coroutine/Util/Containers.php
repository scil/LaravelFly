<?php

namespace LaravelFly\Coroutine\Util;


trait Containers
{
    /**
     * array of The IoC container instances
     *
     * @var array of \LaravelFly\Coroutine\Application
     */
    protected $containers;

    protected function getCurrentContainer()
    {
        return $this->containers[\Swoole\Coroutine::getuid()];
    }


}