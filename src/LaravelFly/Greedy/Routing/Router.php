<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/9/9
 * Time: 1:31
 *
 * only for compiling all routes made before any request
 */

namespace LaravelFly\Greedy\Routing;

use Illuminate\Routing\Route as BaseRoute;

class Router extends \Illuminate\Routing\Router
{

    protected $beforeAppBooted = true;

    public function appBooted()
    {
        $this->beforeAppBooted = false;
    }

    /**
     * Override
     */
    protected function newRoute($methods, $uri, $action)
    {
        if ($this->beforeAppBooted) {
            // before any request, routes are compiled auto.
            return (new Route($methods, $uri, $action))->setContainer($this->container);
        } else {
            // routes creaed during request are not compiled auto. They are compiled when match
            return (new BaseRoute($methods, $uri, $action))->setContainer($this->container);
        }
    }
}