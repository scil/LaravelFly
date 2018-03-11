<?php
/**
 * Created by PhpStorm.
 * User: iv
 * Date: 2018/1/30
 * Time: 1:44
 */

namespace LaravelFly\Map\Illuminate\Session;


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
}