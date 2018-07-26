<?php

namespace LaravelFly\Map\Illuminate\Session;

use LaravelFly\Map\Util\Dict;
use SessionHandlerInterface;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class CookieSessionHandler extends \Illuminate\Session\CookieSessionHandler
{
    use Dict;
    protected static $normalAttriForObj = ['request' => null,];

    public function __construct(CookieJar $cookie, $minutes)
    {
        $this->initOnWorker( true);
        parent::__construct($cookie, $minutes);
    }

    public function read($sessionId)
    {
        $value = static::$corDict[\Co::getUid()]['request']->cookies->get($sessionId) ?: '';

        if (!is_null($decoded = json_decode($value, true)) && is_array($decoded)) {
            if (isset($decoded['expires']) && $this->currentTime() <= $decoded['expires']) {
                return $decoded['data'];
            }
        }

        return '';
    }

    public function setRequest(Request $request)
    {
        static::$corDict[\Co::getUid()]['request'] = $request;
    }
}
