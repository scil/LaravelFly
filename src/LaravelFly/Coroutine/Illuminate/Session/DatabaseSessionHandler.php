<?php

namespace LaravelFly\Coroutine\Illuminate\Session;
;

use Illuminate\Support\Arr;
use LaravelFly\Coroutine\Util\Dict;
use SessionHandlerInterface;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\QueryException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Container\Container;

class DatabaseSessionHandler extends \Illuminate\Session\DatabaseSessionHandler
{
    use Dict;
    protected $normalAttriForObj = ['exists' => null,];

    public function __construct(ConnectionInterface $connection, $table, $minutes, Container $container = null)
    {
        $this->initForCorontine(-1, true);
        parent::__construct($connection, $table, $minutes, $container);
    }

    public function read($sessionId)
    {
        $session = (object)$this->getQuery()->find($sessionId);

        if ($this->expired($session)) {
            $this->corDict[\Swoole\Coroutine::getuid()]['exists'] = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->corDict[\Swoole\Coroutine::getuid()]['exists'] = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    public function write($sessionId, $data)
    {
        $payload = $this->getDefaultPayload($data);

        $cid = \Swoole\Coroutine::getuid();

        if (!$this->corDict[$cid]['exists']) {
            $this->read($sessionId);
        }

        if ($this->corDict[$cid]['exists']) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }

        return $this->corDict[$cid]['exists'] = true;
    }

    public function setExists($value)
    {
        $this->corDict[\Swoole\Coroutine::getuid()]['exists'] = $value;

        return $this;
    }
}
