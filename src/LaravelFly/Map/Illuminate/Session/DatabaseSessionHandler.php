<?php

namespace LaravelFly\Map\Illuminate\Session;
;

use Illuminate\Support\Arr;
use LaravelFly\Map\Util\Dict;
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
    protected static $normalAttriForObj = ['exists' => null,];

    public function __construct(ConnectionInterface $connection, $table, $minutes, Container $container = null)
    {
        $this->initOnWorker( true);
        parent::__construct($connection, $table, $minutes, $container);
    }

    public function read($sessionId)
    {
        $session = (object)$this->getQuery()->find($sessionId);

        if ($this->expired($session)) {
            static::$corDict[\Co::getUid()]['exists'] = true;

            return '';
        }

        if (isset($session->payload)) {
            static::$corDict[\Co::getUid()]['exists'] = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    public function write($sessionId, $data)
    {
        $payload = $this->getDefaultPayload($data);

        $cid = \Co::getUid();

        if (!static::$corDict[$cid]['exists']) {
            $this->read($sessionId);
        }

        if (static::$corDict[$cid]['exists']) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }

        return static::$corDict[$cid]['exists'] = true;
    }

    public function setExists($value)
    {
        static::$corDict[\Co::getUid()]['exists'] = $value;

        return $this;
    }
}
