<?php

namespace LaravelFly\Hash\Illuminate\Auth;


use Closure;

class AuthManager extends \Illuminate\Auth\AuthManager
{
    use \LaravelFly\Hash\Util\Dict;

    protected static $normalAttriForObj=['userResolver'=>null];

    protected static $arrayAttriForObj = [
        'guards',
        // 'customCreators'
        ];


    public function __construct($app)
    {

        $this->app = $app;

        $this->initOnWorker(true);

        // this statement must be after initOnWorker
        static::$corDict[WORKER_COROUTINE_ID]['userResolver'] = function ($guard = null) {
            return $this->guard($guard)->user();
        };

    }

    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        $cid = \co::getUid();
        return static::$corDict[$cid]['guards'][$name] ??
            (static::$corDict[$cid]['guards'][$name] = $this->resolve($name));
    }

    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);

        static::$corDict[\co::getUid()]['userResolver'] = function ($name = null) {
            return $this->guard($name)->user();
        };
    }
    public function userResolver()
    {
        return static::$corDict[\co::getUid()]['userResolver'];
    }
    public function resolveUsersUsing(Closure $userResolver)
    {
        static::$corDict[\co::getUid()]['userResolver'] = $userResolver;

        return $this;
    }
}

