<?php

namespace LaravelFly\Server;

interface ServerInterface
{

    public function getDispatcher();

    public function config(array $options);

    public function getConfig($name = null);

    public function createSwooleServer();

    public function setListeners();

    public function start();

    public function path($path = null);

}