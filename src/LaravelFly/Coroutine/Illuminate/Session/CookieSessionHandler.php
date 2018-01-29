<?php

namespace LaravelFly\Coroutine\Illuminate\Session;

use LaravelFly\Coroutine\Util\Dict;
use SessionHandlerInterface;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class CookieSessionHandler extends \Illuminate\Session\CookieSessionHandler
{
    use Dict;
    protected $normalAttriForObj = ['request' => null,];

    public function __construct(CookieJar $cookie, $minutes)
    {
        $this->initOnWorker( true);
        parent::__construct($cookie, $minutes);
    }

    public function read($sessionId)
    {
        $value = $this->corDict[\Swoole\Coroutine::getuid()]['request']->cookies->get($sessionId) ?: '';

        if (!is_null($decoded = json_decode($value, true)) && is_array($decoded)) {
            if (isset($decoded['expires']) && $this->currentTime() <= $decoded['expires']) {
                return $decoded['data'];
            }
        }

        return '';
    }

    public function setRequest(Request $request)
    {
        $this->corDict[\Swoole\Coroutine::getuid()]['request'] = $request;
    }
}
