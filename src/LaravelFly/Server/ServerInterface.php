<?php

namespace LaravelFly\Server;

interface ServerInterface
{
    public function create();

    public function setListeners();

    public function start();

    public function path($path = null);

    public function getAppType();
}