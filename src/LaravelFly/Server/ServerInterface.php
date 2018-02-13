<?php
namespace LaravelFly\Server;

interface ServerInterface
{
    public function path($path = null);
    public function setListeners();
    public function start();
}