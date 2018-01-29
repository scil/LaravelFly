<?php

namespace LaravelFly\Coroutine\Illuminate\Auth;


use Closure;

class AuthManager extends \Illuminate\Auth\AuthManager
{
    use \LaravelFly\Coroutine\Util\Dict;

    protected $normalAttriForObj=['userResolver'=>null];

    protected $arrayAttriForObj = [
        'guards',
        // 'customCreators'
        ];


    public function __construct($app)
    {

        $this->app = $app;

        $this->initOnWorker(true);

        // this statement must be after initOnWorker
        $this->corDict[-1]['userResolver'] = function ($guard = null) {
            return $this->guard($guard)->user();
        };

    }

    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        $cid = \Swoole\Coroutine::getuid();
        return $this->corDict[$cid]['guards'][$name] ??
            ($this->corDict[$cid]['guards'][$name] = $this->resolve($name));
    }

    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);

        $this->corDict[\Swoole\Coroutine::getuid()]['userResolver'] = function ($name = null) {
            return $this->guard($name)->user();
        };
    }
    public function userResolver()
    {
        return $this->corDict[\Swoole\Coroutine::getuid()]['userResolver'];
    }
    public function resolveUsersUsing(Closure $userResolver)
    {
        $this->corDict[\Swoole\Coroutine::getuid()]['userResolver'] = $userResolver;

        return $this;
    }
}

