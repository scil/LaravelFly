<?php

namespace Illuminate\Support\Facades;

use Mockery;
use RuntimeException;
use Mockery\MockInterface;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance = [];

    static function initForRequestCorontine(int $cid)
    {
        static::$resolvedInstance[$cid] = static::$resolvedInstance[WORKER_COROUTINE_ID];
    }

    static function unsetForRequestCorontine(int $cid)
    {
        unset(static::$resolvedInstance[$cid]);
    }

    public static function initOnWorker(array $array)
    {
        static::$resolvedInstance[WORKER_COROUTINE_ID] = [];
        foreach ($array as $alias => $instance) {
            static::$resolvedInstance[WORKER_COROUTINE_ID][$alias] = $instance;
        }
    }

    /**
     * Convert the facade into a Mockery spy.
     *
     * @return void
     */
    public static function spy()
    {
        if (!static::isMock(\Swoole\Coroutine::getuid())) {
            $class = static::getMockableClass();

            static::swap($class ? Mockery::spy($class) : Mockery::spy());
        }
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @return \Mockery\Expectation
     */
    public static function shouldReceive()
    {
        $name = static::getFacadeAccessor();

        $cid = \Co::getUid();

        $mock = static::isMock($cid)
            ? static::$resolvedInstance[$cid][$name]
            : static::createFreshMockInstance();

        return $mock->shouldReceive(...func_get_args());
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\Expectation
     */
    protected static function createFreshMockInstance()
    {
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);

            $mock->shouldAllowMockingProtectedMethods();
        });
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\MockInterface
     */
    protected static function createMock()
    {
        $class = static::getMockableClass();

        return $class ? Mockery::mock($class) : Mockery::mock();
    }

    /**
     * Determines whether a mock is set as the instance of the facade.
     *
     * @return bool
     */
    protected static function isMock($cid)
    {
        $name = static::getFacadeAccessor();

        return isset(static::$resolvedInstance[$cid][$name]) &&
            static::$resolvedInstance[$cid][$name] instanceof MockInterface;
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        if ($root = static::getFacadeRoot()) {
            return get_class($root);
        }
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed $instance
     * @return void
     */
    public static function swap($instance)
    {
        static::$resolvedInstance[\Co::getUid()][static::getFacadeAccessor()] = $instance;

        if (isset(static::$app)) {
            static::$app->instance(static::getFacadeAccessor(), $instance);
        }
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string|object $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        $cid = \Co::getUid();

        if (isset(static::$resolvedInstance[$cid][$name])) {
            return static::$resolvedInstance[$cid][$name];
        }

        return static::$resolvedInstance[$cid][$name] = static::$app[$name];
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param  string $name
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[\Co::getUid()][$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance[\Co::getUid()] = [];
    }

    /**
     * Get the application instance behind the facade.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }

    /**
     * Set the application instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
