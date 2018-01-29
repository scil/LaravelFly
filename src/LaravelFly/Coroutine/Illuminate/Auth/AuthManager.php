<?php

namespace LaravelFly\Coroutine\Illuminate\Auth;


class AuthManager extends \Illuminate\Auth\AuthManager
{
    use \LaravelFly\Coroutine\Util\Dict;

    protected $arrayAttriForObj = ['guards',];

    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        $cid = \Swoole\Coroutine::getuid();
        return $this->corDict[$cid]['guards'][$name] ??
            ($this->corDict[$cid]['guards'][$name] = $this->resolve($name));
    }

}

