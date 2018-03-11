<?php

namespace LaravelFly\Map\IlluminateBase;

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

    use \LaravelFly\Map\Util\Dict;

    protected static $normalAttriForObj=['queueResolver'=>null];

    protected static $arrayAttriForObj=['listeners','wildcards'];

    public function __construct(ContainerContract $container)
    {
        $this->container = $container;
        $this->initOnWorker(false);
    }

    public function listen($events, $listener)
    {
        foreach ((array)$events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                static::$corDict[\co::getUid()]['listeners'][$event][] = $this->makeListener($listener);
            }
        }
    }

    protected function setupWildcardListen($event, $listener)
    {
        static::$corDict[\co::getUid()]['wildcards'][$event][] = $this->makeListener($listener, true);
    }

    public function hasListeners($eventName)
    {
        $current = static::$corDict[\co::getUid()];
        return isset($current['listeners'][$eventName]) || isset($current['wildcards'][$eventName]);
    }

    //todo
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    protected function broadcastEvent($event)
    {
        $this->container->make(BroadcastFactory::class)->queue($event);
    }

    public function getListeners($eventName)
    {
        $listeners = static::$corDict[\co::getUid()]['listeners'][$eventName] ?? [];

        $listeners = array_merge(
            $listeners, $this->getWildcardListeners($eventName)
        );

        return class_exists($eventName, false)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : $listeners;
    }

    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        foreach (static::$corDict[\co::getUid()]['wildcards'] as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $wildcards;
    }

    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        $c = \co::getUid();
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$c][$interface])) {
                foreach ($this->listeners[$c][$interface] as $names) {
                    $listeners = array_merge($listeners, (array)$names);
                }
            }
        }

        return $listeners;
    }

    protected function createClassCallable($listener)
    {
        list($class, $method) = $this->parseClassCallable($listener);

        if ($this->handlerShouldBeQueued($class)) {
            return $this->createQueuedHandlerCallable($class, $method);
        }

        return [$this->container->make($class), $method];
    }

    protected function handlerWantsToBeQueued($class, $arguments)
    {
        if (method_exists($class, 'shouldQueue')) {
            return $this->container->make($class)->shouldQueue($arguments[0]);
        }

        return true;
    }

    public function forget($event)
    {
        $cid = \co::getUid();
        if (Str::contains($event, '*')) {
            unset(static::$corDict[$cid]['wildcards'][$event]);
        } else {
            unset(static::$corDict[$cid]['listeners'][$event]);
        }
    }

    public function forgetPushed()
    {
        $c = \co::getUid();
        foreach ($this->listeners[$c] as $key => $value) {
            if (Str::endsWith($key, '_pushed')) {
                $this->forget($key);
            }
        }

    }

    protected function resolveQueue()
    {
        return call_user_func(static::$corDict[\co::getUid()]['queueResolver']);
    }

    public function setQueueResolver(callable $resolver)
    {
        static::$corDict[\co::getUid()]['queueResolver'] = $resolver;

        return $this;
    }
}
