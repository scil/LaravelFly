<?php

namespace LaravelFly\Map\Illuminate\Auth;


use Closure;

class AuthManagerSame extends \Illuminate\Auth\AuthManager
{
    use \LaravelFly\Map\Util\Dict;

    // todo needed really?
    protected static $normalAttriForObj = ['userResolver' => null];

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

        $cid = \Co::getUid();
        return static::$corDict[$cid]['guards'][$name] ??
            (static::$corDict[$cid]['guards'][$name] = $this->resolve($name));
    }

    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultDriver();

// todo this file seems useless? oh no! and we need work more! config maybe changed .see:
// You may have wondered why when using the apigroup of middleware that $request->user() returns the correct user from the api guard and doesn't use the default web guard
// https://asklagbox.com/blog/unboxing-laravel-authentication#the-user-resolver
        $this->setDefaultDriver($name);

        static::$corDict[\Co::getUid()]['userResolver'] = function ($name = null) {
            return $this->guard($name)->user();
        };
    }

    public function userResolver()
    {
        return static::$corDict[\Co::getUid()]['userResolver'];
    }

    public function resolveUsersUsing(Closure $userResolver)
    {
        static::$corDict[\Co::getUid()]['userResolver'] = $userResolver;

        return $this;
    }
}

