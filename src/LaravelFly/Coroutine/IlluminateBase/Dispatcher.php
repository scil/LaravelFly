<?php

namespace LaravelFly\Coroutine\IlluminateBase;

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
    use \LaravelFly\Coroutine\Util\Containers;

    /**
     * The queue resolver instance.
     *
     * @var array of callable
     */
    protected $queueResolver;


    public function __construct(ContainerContract $container)
    {
        $this->container = $container;
        $this->initForOneCoroutine(-1);
    }

    public function initForOneCoroutine(int $id )
    {

        $this->containers[$id] = Container::getInstance();
        /**
         * copy values of worker instance to request instance
         *
         * why not let them apart, it's ok for get/set actions.like this get action:
         * <code>
         *  array_merge( $this->listeners[\Swoole\Coroutine::getuid()], $this->listeners[-1])
         * </code>
         *
         * but there's a method {@link forget()}, it's a del action. Should i delete $this->listeners[-1] or not?
         *
         */
        //todo deep clone ?
        $this->listeners[$id] = $id==-1? []: $this->listeners[-1];
        $this->wildcards[$id] = $id==-1? []: $this->wildcards[-1];
        //todo clone?
        $this->queueResolver[$id] = $id==-1?null: $this->queueResolver[-1];
    }
    public function delDataForCoroutine(int $id )
    {
       unset($this->containers[$id], $this->listeners[$id],$this->wildcards[$id],$this->queueResolver[$id]);
    }

    public function listen($events, $listener)
    {
        foreach ((array)$events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[\Swoole\Coroutine::getuid()][$event][] = $this->makeListener($listener);
            }
        }
    }

    protected function setupWildcardListen($event, $listener)
    {
        $this->wildcards[\Swoole\Coroutine::getuid()][$event][] = $this->makeListener($listener, true);
    }

    public function hasListeners($eventName)
    {
        $cid = \Swoole\Coroutine::getuid();
        return isset($this->listeners[$cid][$eventName]) || isset($this->wildcards[$cid][$eventName]);
    }

    //todo
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->getCurrentContainer()->make($subscriber);
        }

        return $subscriber;
    }

    protected function broadcastEvent($event)
    {
        $this->getCurrentContainer()->make(BroadcastFactory::class)->queue($event);
    }

    public function getListeners($eventName)
    {
        $listeners = $this->listeners[\Swoole\Coroutine::getuid()][$eventName] ?? [];

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

        foreach ($this->wildcards[\Swoole\Coroutine::getuid()] as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $wildcards;
    }

    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        $c = \Swoole\Coroutine::getuid();
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

        return [$this->getCurrentContainer()->make($class), $method];
    }

    protected function handlerWantsToBeQueued($class, $arguments)
    {
        if (method_exists($class, 'shouldQueue')) {
            return $this->getCurrentContainer()->make($class)->shouldQueue($arguments[0]);
        }

        return true;
    }

    public function forget($event)
    {
        $cid = \Swoole\Coroutine::getuid();
        if (Str::contains($event, '*')) {
            unset($this->wildcards[$cid][$event]);
        } else {
            unset($this->listeners[$cid][$event]);
        }
    }

    public function forgetPushed()
    {
        $c = \Swoole\Coroutine::getuid();
        foreach ($this->listeners[$c] as $key => $value) {
            if (Str::endsWith($key, '_pushed')) {
                $this->forget($key);
            }
        }

    }

    protected function resolveQueue()
    {
        return call_user_func($this->queueResolver[\Swoole\Coroutine::getuid()]);
    }

    public function setQueueResolver(callable $resolver)
    {
        $this->queueResolver[\Swoole\Coroutine::getuid()] = $resolver;

        return $this;
    }
}
