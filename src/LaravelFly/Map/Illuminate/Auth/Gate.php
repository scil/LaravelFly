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
        'stringCallbacks',
        'guessPolicyNamesUsingCallback',
    ];

    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [],
                                callable $guessPolicyNamesUsingCallback = null)
    {

        $this->container = $container;

        $this->initOnWorker(true);

        static::$corDict[WORKER_COROUTINE_ID]['userResolver'] = $userResolver;
        static::$corDict[WORKER_COROUTINE_ID]['policies'] = $policies;
        static::$corDict[WORKER_COROUTINE_ID]['abilities'] = $abilities;
        static::$corDict[WORKER_COROUTINE_ID]['afterCallbacks'] = $afterCallbacks;
        static::$corDict[WORKER_COROUTINE_ID]['beforeCallbacks'] = $beforeCallbacks;
        static::$corDict[WORKER_COROUTINE_ID]['guessPolicyNamesUsingCallback'] = $guessPolicyNamesUsingCallback;
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
        } elseif (is_string($callback)) {
	     static::$corDict[$cid]['stringCallbacks'][$ability] = $callback;
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
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
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
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string $ability
     * @param  array $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {

        foreach (static::$corDict[\Swoole\Coroutine::getuid()]['beforeCallbacks'] as $before) {
            if (! $this->canBeCalledWithUser($user, $before)) {
                continue;
            }
            if (! is_null($result = $before($user, $ability, $arguments))) {
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
     * @return bool|null
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        foreach (static::$corDict[\Swoole\Coroutine::getuid()]['afterCallbacks'] as $after) {
            if (! $this->canBeCalledWithUser($user, $after)) {
                continue;
            }

            $afterResult = $after($user, $ability, $result, $arguments);

            $result = $result ?? $afterResult;
        }

        return $result;

    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
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

	$s = static::$corDict[\Swoole\Coroutine::getuid()]['stringCallbacks'];
        $b = static::$corDict[\Swoole\Coroutine::getuid()]['abilities'];
        
        if (isset($s[$ability])) {
            [$class, $method] = Str::parseCallback($s[$ability]);

            if ($this->canBeCalledWithUser($user, $class, $method ?: '__invoke')) {
                return $b[$ability];
            }
        }


        if (isset($b[$ability]) &&
	        $this->canBeCalledWithUser($user, $b[$ability]) ) {
            return $b[$ability];
        }

        return function () {
            return null;
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
        
        foreach ($this->guessPolicyName($class) as $guessedPolicy) {
            if (class_exists($guessedPolicy)) {
                return $this->resolvePolicy($guessedPolicy);
            }
        }

        foreach (static::$corDict[$cid]['policies'] as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Guess the policy name for the given class.
     *
     * @param  string  $class
     * @return array
     */
    protected function guessPolicyName($class)
    {
        if ( $g = static::$corDict[\Swoole\Coroutine::getuid()]['guessPolicyNamesUsingCallback'] ) {
            return Arr::wrap(call_user_func($g, $class));
        }

        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));

        return [$classDirname.'\\Policies\\'.class_basename($class).'Policy'];
    }

    /**
     * Specify a callback to be used to guess policy names.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function guessPolicyNamesUsing(callable $callback)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['guessPolicyNamesUsingCallback']  = $callback;

        return $this;
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
            static::$corDict[$cid]['policies'], static::$corDict[$cid]['beforeCallbacks'], static::$corDict[$cid]['afterCallbacks'],
            static::$corDict[$cid]['guessPolicyNamesUsingCallback']
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
