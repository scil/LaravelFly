<?php
/**
 * add Dict, plus
 * listeners cache
 *      this var across multple requests, changed in any request would change this var
 *          static $listenersStalbe = [];
 *
 */

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

    protected static $normalAttriForObj = ['queueResolver' => null];

    protected static $arrayAttriForObj = ['listeners', 'wildcards', 'wildcardsCache'];

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
                static::$listenersStalbe[$event] = false;
                static::$corDict[\co::getUid()]['listeners'][$event][] = $this->makeListener($listener);
            }
        }
    }

    protected function setupWildcardListen($event, $listener)
    {
        static::$corDict[\co::getUid()]['wildcards'][$event][] = $this->makeListener($listener, true);

        static::$corDict[\co::getUid()]['wildcardsCache'] = [];

        foreach (array_keys(static::$listenersStalbe) as $eventName) {
            if (Str::is($event, $eventName)) {
                static::$listenersStalbe[$eventName] = false;
            }
        }
    }

    public function hasListeners($eventName)
    {
        $current = static::$corDict[\co::getUid()];
        return isset($current['listeners'][$eventName]) || isset($current['wildcards'][$eventName]);
    }

    // hack
    static $listenersStalbe = [];

    public function getListeners($eventName)
    {
        // hack
        static $cache = [];
        if (!empty(static::$listenersStalbe[$eventName])) return $cache[$eventName];
        static::$listenersStalbe[$eventName] = true;

        $listeners = static::$corDict[\co::getUid()]['listeners'][$eventName] ?? [];

        $listeners = array_merge(
            $listeners,
            static::$corDict[\co::getUid()]['wildcardsCache'][$eventName] ?? $this->getWildcardListeners($eventName)
        );

        // hack
        return $cache[$eventName] = class_exists($eventName, false)
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

        return static::$corDict[\co::getUid()]['wildcardsCache'][$eventName] = $wildcards;
    }

    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        $current = static::$corDict[\co::getUid()]['listeners'];

        foreach (class_implements($eventName) as $interface) {
            if (isset($current[$interface])) {
                foreach ($current[$interface] as $names) {
                    $listeners = array_merge($listeners, (array)$names);
                }
            }
        }

        return $listeners;
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
        foreach (static::$corDict[\co::getUid()]['listeners'] as $key => $value) {
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
