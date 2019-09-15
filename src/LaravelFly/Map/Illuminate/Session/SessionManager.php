<?php
/**
 * 1. COROUTINE-FRIENDLY
 * 2. SwooleSessionHandler
 */

namespace LaravelFly\Map\Illuminate\Session;


use LaravelFly\Map\Illuminate\Session\Swoole\SwooleSessionHandler;
use LaravelFly\Server\Common;


class SessionManager extends \Illuminate\Session\SessionManager
{
    protected function createCookieDriver()
    {
        return $this->buildSession(new CookieSessionHandler(
            $this->container->make('cookie'), $this->config->get('session.lifetime')
        ));
    }

    protected function createDatabaseDriver()
    {
        $table = $this->config->get('session.table');

        $lifetime = $this->config->get('session.lifetime');

        return $this->buildSession(new DatabaseSessionHandler(
            $this->getDatabaseConnection(), $table, $lifetime, $this->container
        ));
    }

    protected function createSwooleDriver()
    {
        /**
         * @var Common $server
         */
        $server = \LaravelFly\Fly::getServer();
        return $this->buildSession(new SwooleSessionHandler(
            $server->getTableMemory('swooleSession'), $this->config->get('session.lifetime')
        ));
    }
}