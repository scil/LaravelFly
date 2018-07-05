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
        $cid=\Swoole\Coroutine::getuid();

        return [$path ?: static::$corDict[$cid]['path'], $domain ?: static::$corDict[$cid]['domain'], is_bool($secure) ? $secure : static::$corDict[$cid]['secure'], $sameSite ?: static::$corDict[$cid]['sameSite']];
    }

    public function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
    {
        $cid=\Swoole\Coroutine::getuid();

        list(static::$corDict[$cid]['path'], static::$corDict[$cid]['domain'], static::$corDict[$cid]['secure'], static::$corDict[$cid]['sameSite']) = [$path, $domain, $secure, $sameSite];

        return $this;
    }
}
