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

    public function queued($key, $default = null, $path = null)
    {
        $queued =  Arr::get(static::$corDict[\Co::getUid()]['queued'], $key, $default);
        
        if ($path === null) {
            return Arr::last($queued, null, $default);
        }

        return Arr::get($queued, $path, $default);

    }

    public function queue(...$parameters)
    {
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }
        
        $Q  = & static::$corDict[\Co::getUid()]['queued'];
        if (! isset($Q[$cookie->getName()])) {
            $Q[$cookie->getName()] = [];
        }

        $Q[$cookie->getName()][$cookie->getPath()] = $cookie;

    }

    public function unqueue($name, $path = null)
    {
        $Q  = & static::$corDict[\Co::getUid()]['queued'];
        
        if ($path === null) {
            unset($Q[$name]);
            return;
        }
        
        unset($Q[$name][$path]);

        if (empty($Q[$name])) {
            unset($Q[$name]);
        }

        
    }

    public function getQueuedCookies()
    {
        return Arr::flatten(static::$corDict[\Co::getUid()]['queued']);
    }
}
