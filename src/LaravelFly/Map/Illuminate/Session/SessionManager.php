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
            $this->app['cookie'], $this->app['config']['session.lifetime']
        ));
    }

    protected function createDatabaseDriver()
    {
        $table = $this->app['config']['session.table'];

        $lifetime = $this->app['config']['session.lifetime'];

        return $this->buildSession(new DatabaseSessionHandler(
            $this->getDatabaseConnection(), $table, $lifetime, $this->app
        ));
    }

    protected function createSwooleDriver()
    {
        /**
         * @var Common $server
         */
        $server = \LaravelFly\Fly::getServer();
        return $this->buildSession(new SwooleSessionHandler(
            $server->getTableMemory('swooleSession'), $this->app['config']['session.lifetime']
        ));
    }
}