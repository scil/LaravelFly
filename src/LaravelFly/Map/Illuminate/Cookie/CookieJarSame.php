<?php

namespace LaravelFly\Map\Illuminate\Cookie;

use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use LaravelFly\Map\Util\Dict;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJarSame extends \Illuminate\Cookie\CookieJar
{
    use Dict;
    protected static $arrayAttriForObj = ['queued',];

    public function __construct()
    {
        $this->initOnWorker( true);
    }

    public function queued($key, $default = null)
    {
        return Arr::get(static::$corDict[\Co::getUid()]['queued'], $key, $default);
    }

    public function queue(...$parameters)
    {
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }

        static::$corDict[\Co::getUid()]['queued'][$cookie->getName()] = $cookie;
    }

    public function unqueue($name)
    {
        unset(static::$corDict[\Co::getUid()]['queued'][$name]);
    }

    public function getQueuedCookies()
    {
        return static::$corDict[\Co::getUid()]['queued'];
    }
}
