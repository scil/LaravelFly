<?php
namespace LaravelFly\Server;

interface ServerInterface
{
    public function setListeners();
    public function start();
}