<?php

namespace LaravelFly\Coroutine\Illuminate;

use Exception;
use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Dispatcher extends \Illuminate\Events\Dispatcher
{
   function __clone()
   {
       $this->container = Container::getInstance();
       //todo
       // queueResolver

   }
}
