<?php

namespace LaravelFly\Coroutine\Illuminate\Cookie;

use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use LaravelFly\Coroutine\Util\Dict;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar extends \Illuminate\Cookie\CookieJar
{
    use Dict;
    protected $arrayAttriForObj = ['queued',];

    public function __construct()
    {
        $this->initForCorontine(-1, true);
    }

    public function queued($key, $default = null)
    {
        return Arr::get($this->corDict[\Swoole\Coroutine::getuid()]['queued'], $key, $default);
    }

    public function queue(...$parameters)
    {
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }

        $this->corDict[\Swoole\Coroutine::getuid()]['queued'][$cookie->getName()] = $cookie;
    }

    public function unqueue($name)
    {
        unset($this->corDict[\Swoole\Coroutine::getuid()]['queued'][$name]);
    }

    public function getQueuedCookies()
    {
        return $this->corDict[\Swoole\Coroutine::getuid()]['queued'];
    }
}
