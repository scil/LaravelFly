<?php

namespace LaravelFly\Server;

use Symfony\Component\EventDispatcher\EventDispatcher;

interface ServerInterface
{

    public function getDispatcher(): EventDispatcher;

    public function config(array $options);

    public function getConfig($name = null);

    public function createSwooleServer();

    public function setListeners();

    public function start();

    public function path($path = null);

    public function echo($text, $status = 'INFO', $color = false);
}