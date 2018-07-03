<?php

namespace LaravelFly\Map\Illuminate\Auth;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class Gate extends \Illuminate\Auth\Access\Gate
{
    use \LaravelFly\Map\Util\Dict;

    // todo needed really?
    protected static $normalAttriForObj = [
        'userResolver' => null
    ];

    protected static $arrayAttriForObj = [
        'abilities',
        'policies',
        'beforeCallbacks',
        'afterCallbacks',
    ];

    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [])
    {

        $this->container = $container;

        $this->initOnWorker(true);

        static::$corDict[WORKER_COROUTINE_ID]['userResolver'] = $userResolver;
        static::$corDict[WORKER_COROUTINE_ID]['policies'] = $policies;
        static::$corDict[WORKER_COROUTINE_ID]['abilities'] = $abilities;
        static::$corDict[WORKER_COROUTINE_ID]['afterCallbacks'] = $afterCallbacks;
        static::$corDict[WORKER_COROUTINE_ID]['beforeCallbacks'] = $beforeCallbacks;
    }

    public function has($ability)
    {
        $abilities = is_array($ability) ? $ability : func_get_args();

        $allB = static::$corDict[\Swoole\Coroutine::getuid()]['abilities'];

        foreach ($abilities as $ability) {
            if (!isset($allB[$ability])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define a new ability.
     *
     * @param  string $ability
     * @param  callable|string $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        $cid = \Swoole\Coroutine::getuid();

        if (is_callable($callback)) {
            static::$corDict[$cid]['abilities'][$ability] = $callback;
        } elseif (is_string($callback) && Str::contains($callback, '@')) {
            static::$corDict[$cid]['abilities'][$ability] = $this->buildAbilityCallback($ability, $callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string $class
     * @param  string $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['policies'][$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param  callable $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['beforeCallbacks'][] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param  callable $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['afterCallbacks'][] = $callback;

        return $this;
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        $arguments = array_merge([$user, $ability], [$arguments]);

        foreach (static::$corDict[\Swoole\Coroutine::getuid()]['beforeCallbacks'] as $before) {
            if (!is_null($result = $before(...$arguments))) {
                return $result;
            }
        }
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     * @param  bool $result
     * @return void
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        $arguments = array_merge([$user, $ability, $result], [$arguments]);

        foreach (static::$corDict[\Swoole\Coroutine::getuid()]['afterCallbacks'] as $after) {
            $after(...$arguments);
        }
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0]) &&
            !is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }

        $cid = \Swoole\Coroutine::getuid();

        if (isset(static::$corDict[$cid]['abilities'][$ability])) {
            return static::$corDict[$cid]['abilities'][$ability];
        }

        return function () {
            return false;
        };
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string $class
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!is_string($class)) {
            return;
        }

        $cid = \Swoole\Coroutine::getuid();

        if (isset(static::$corDict[$cid]['policies'][$class])) {
            return $this->resolvePolicy(static::$corDict[$cid]['policies'][$class]);
        }

        foreach (static::$corDict[$cid]['policies'] as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        $cid = \Swoole\Coroutine::getuid();

        return new static(
            $this->container, $callback, static::$corDict[$cid]['abilities'],
            static::$corDict[$cid]['policies'], static::$corDict[$cid]['beforeCallbacks'], static::$corDict[$cid]['afterCallbacks']
        );
    }

    protected function resolveUser()
    {
        return call_user_func(static::$corDict[\Swoole\Coroutine::getuid()]['userResolver']);
    }

    /**
     * Get all of the defined abilities.
     *
     * @return array
     */
    public function abilities()
    {
        return static::$corDict[\Swoole\Coroutine::getuid()]['abilities'];
    }

    /**
     * Get all of the defined policies.
     *
     * @return array
     */
    public function policies()
    {
        return static::$corDict[\Swoole\Coroutine::getuid()]['policies'];
    }
}
