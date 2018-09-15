<?php
/**
 *
 * 1.    $cacheByRoute and $cacheForterminate
 *   note that this cache is totally useless when a route middleware may be registered in a request.
 *   so vars are across multple requests, changed in any request would change this var
 *
 *   update: when LARAVELFLY_SERVICES['kernel'] && LARAVELFLY_SERVICES['routes'], $middlewareStable is useless and cache is used always
 *        $middlewareAlwaysStable
 *
 * 2.   $singletonMiddlewares
 */

namespace LaravelFly\Map\CommonHack;

use Illuminate\Routing\Route;
use Illuminate\Routing\MiddlewareNameResolver;

trait RouterMiddleware
{
    static $middlewareStable = false;

    static $middlewareAlwaysStable = false;

    /**
     * @param bool $middlewareAlwaysStable
     */
    public function enableMiddlewareAlwaysStable(): void
    {
        self::$middlewareAlwaysStable = true;
    }

    /**
     * @param $name
     * @param $class
     * @return $this
     * @overwrite
     */
    public function aliasMiddleware($name, $class)
    {
        static::$corDict[\Co::getUid()]['middleware'][$name] = $class;

        // hack
        self::$middlewareAlwaysStable || (static::$middlewareStable = false);

        return $this;
    }

    /**
     * Register a group of middleware.
     *
     * @param  string $name
     * @param  array $middleware
     * @return $this
     * @overwrite
     */
    public function middlewareGroup($name, array $middleware)
    {
        static::$corDict[\Co::getUid()]['middlewareGroups'][$name] = $middleware;

        // hack
        self::$middlewareAlwaysStable || (static::$middlewareStable = false);

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string $group
     * @param  string $middleware
     * @return $this
     * @overwrite
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        $cid = \Co::getUid();
        if (isset(static::$corDict[$cid]['middlewareGroups'][$group]) && !in_array($middleware, static::$corDict[$cid]['middlewareGroups'][$group])) {

            // hack
            self::$middlewareAlwaysStable || (static::$middlewareStable = false);

            array_unshift(static::$corDict[$cid]['middlewareGroups'][$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string $group
     * @param  string $middleware
     * @return $this
     * @overwrite
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        $cid = \Co::getUid();
        if (!array_key_exists($group, static::$corDict[$cid]['middlewareGroups'])) {
            static::$corDict[$cid]['middlewareGroups'][$group] = [];
        }

        if (!in_array($middleware, static::$corDict[$cid]['middlewareGroups'][$group])) {

            // hack
            self::$middlewareAlwaysStable || (static::$middlewareStable = false);

            static::$corDict[$cid]['middlewareGroups'][$group][] = $middleware;
        }

        return $this;
    }

    /**
     * @param Route $route
     * @param bool $terminal hack: Cache for terminateMiddleware objects.
     * @return array|mixed
     *
     * @overwrite
     */
    public function gatherRouteMiddleware(Route $route, $terminal = false)
    {
        //hack
        static $used = 0,
        $cacheByRoute = [],  // hack: Cache for route middlewares.
        $cacheForterminate = [];// hack: Cache for terminateMiddleware objects. only route middlewares here, no kernel middlewares

        $id = version_compare(PHP_VERSION, '7.2.0', '>=') ? spl_object_id($route) : spl_object_hash($route);

        if ((self::$middlewareAlwaysStable || static::$middlewareStable)
            &&
            isset($cacheByRoute[$id])) {

            ++$used;
            return $terminal ? $cacheForterminate[$id] : $cacheByRoute[$id];
            // return $cacheByRoute[$id];

        }

        self::$middlewareAlwaysStable || (static::$middlewareStable = true);

        $middleware = collect($route->gatherMiddleware())->map(function ($name) {
            $cid = \Co::getUid();
            return (array)MiddlewareNameResolver::resolve($name, static::$corDict[$cid]['middleware'], static::$corDict[$cid]['middlewareGroups']);
        })->flatten();


        // if no cache found for current route's terminal middlewares, return just now. no more making any cache.
        if ($terminal) {
            return $this->sortMiddleware($middleware);
        }

        // by default , cache an empty array
        $cacheForterminate[$id] = [];

        return $cacheByRoute[$id] = array_map(function ($one) use (&$cacheForterminate, $id) {

            // hack: Cache for route middlewares objects.
            static $cacheForObj = [];

            if (is_object($one) && method_exists($one, 'terminate')) {
                $cacheForterminate[$id][] = $one;
                return $one;
            }

            if (is_callable($one)) {
                return $one;
            }

            if (isset($cacheForObj[$one])) {
                return $cacheForObj[$one];
            }

            // store objects (middleware's instance)
            if ($instance = $this->container->getStableMiddlewareInstance($one, static::$singletonMiddlewares)) {
                /**
                 * hack: Cache for terminateMiddleware objects.
                 * @var array
                 */
                if (method_exists($instance, 'terminate')) {
                    $cacheForterminate[$id][] = $instance;
                }
                return $cacheForObj[$one] = $instance;
            }

            // store string (middleware's name)
            $cacheForterminate[$id][] = $one; // hack: Cache for terminateMiddleware objects.
            return $cacheForObj[$one] = $one;

        }, $this->sortMiddleware($middleware));

    }

    // hack: Cache for route middlewares objects.
    static $singletonMiddlewares = [];

    /**
     * hack: Cache for route middlewares objects.
     * @param array $middlewares
     */
    public function setSingletonMiddlewares(array $middlewares): void
    {
        self::$singletonMiddlewares = $middlewares;
    }


}