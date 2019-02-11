<?php

namespace LaravelFly\Map\Illuminate\Cookie;

use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use LaravelFly\Map\Util\Dict;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar extends CookieJarSame
{
    use Dict;
    protected static $arrayAttriForObj = ['queued',];
    protected static $normalAttriForObj = [
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'sameSite' => null,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function getPathAndDomain($path, $domain, $secure = null, $sameSite = null)
    {
        $dict = static::$corDict[\Swoole\Coroutine::getuid()];

        return [$path ?: $dict['path'], $domain ?: $dict['domain'], is_bool($secure) ? $secure : $dict['secure'], $sameSite ?: $dict['sameSite']];
    }

    public function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
    {
        $dict = &static::$corDict[\Swoole\Coroutine::getuid()];

        [$dict['path'], $dict['domain'], $dict['secure'], $dict['sameSite'] ] = [$path, $domain, $secure, $sameSite];

        return $this;
    }
}
