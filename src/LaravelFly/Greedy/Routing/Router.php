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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Container\Container;

class Router extends \Illuminate\Routing\Router
{

    protected $beforeAppBooted = true;
    protected $version;

    public function __construct(Dispatcher $events, Container $container = null)
    {
        parent::__construct($events, $container);
        $this->version = substr($container::VERSION, 0, 3);
    }

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

            if ($this->version == '5.1') {
                return (new Route($methods, $uri, $action))->setContainer($this->container);
            } elseif ($this->version == '5.2') {
                return (new Route($methods, $uri, $action))
                    ->setRouter($this)
                    ->setContainer($this->container);
            }
        } else {
            // routes creaed during request are not compiled auto. They are compiled when match

            if ($this->version == '5.1') {
                return (new BaseRoute($methods, $uri, $action))->setContainer($this->container);
            } elseif ($this->version == '5.2') {
                return (new BaseRoute($methods, $uri, $action))
                    ->setRouter($this)
                    ->setContainer($this->container);

            }
        }
    }
}