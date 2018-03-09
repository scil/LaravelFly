<?php

namespace LaravelFly\Hash\Illuminate\Cookie;

use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use LaravelFly\Hash\Util\Dict;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar extends \Illuminate\Cookie\CookieJar
{
    use Dict;
    protected static $arrayAttriForObj = ['queued',];

    public function __construct()
    {
        $this->initOnWorker( true);
    }

    public function queued($key, $default = null)
    {
        return Arr::get(static::$corDict[\Swoole\Coroutine::getuid()]['queued'], $key, $default);
    }

    public function queue(...$parameters)
    {
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }

        static::$corDict[\Swoole\Coroutine::getuid()]['queued'][$cookie->getName()] = $cookie;
    }

    public function unqueue($name)
    {
        unset(static::$corDict[\Swoole\Coroutine::getuid()]['queued'][$name]);
    }

    public function getQueuedCookies()
    {
        return static::$corDict[\Swoole\Coroutine::getuid()]['queued'];
    }
}
