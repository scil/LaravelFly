<?php
/**
 * User: scil
 * Date: 2018/6/30
 * Time: 22:15
 */

namespace LaravelFly\Tests\Simple\Unit;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use LaravelFly\Tests\BaseTestCase;
use ReflectionClass;
use ReflectionProperty;

class ApplicationTest extends BaseTestCase
{
    var $appProps = [

        'resolved',
        'bindings',
        'methodBindings',
        'instances',
        'aliases',
        'abstractAliases',
        'extenders',
        'tags',

        'buildStack',
        'with',

        'contextual',

        'reboundCallbacks',
        'globalResolvingCallbacks',
        'globalAfterResolvingCallbacks',
        'resolvingCallbacks',
        'afterResolvingCallbacks',


        // ----
        // from Application
        // ----

        'hasBeenBootstrapped',
        'booted',
        'bootingCallbacks',
        'bootedCallbacks',


        'terminatingCallbacks',

        'serviceProviders',
        'loadedProviders',
        'deferredServices',


        'basePath',
        'databasePath',
        'storagePath',
        'environmentPath',
        'environmentFile',
        'namespace',
        'instance',

    ];

    var $eventProps = [
        'container',
        'listeners',
        'wildcards',
        'wildcardsCache',
        'queueResolver',
    ];


    var $routerProps = [
        'middleware', 'middlewareGroups', 'middlewarePriority',
        'binders', 'patterns', 'groupStack',

        'current',
        'currentRequest',


        'events',
        'container',
        'routes',
        'verbs',

        'macros',

    ];


    var $routesProps = [

        'routes', 'allRoutes', 'nameList', 'actionList',
    ];
    var $urlProps = [

        'forcedRoot', 'forceScheme',
        'rootNamespace',
        'sessionResolver', 'keyResolver',
        'formatHostUsing', 'formatPathUsing',

        'macros',

        'request',
        'routeGenerator', 'cachedRoot', 'cachedSchema',
        'routes'
    ];

    function testOfficialApplicationProps()
    {

        $this->props($this->appProps, 'Illuminate\Foundation\Application');
    }

    function testOfficialEventProps()
    {
        $this->props($this->eventProps, 'Illuminate\Events\Dispatcher');
    }

    function testOfficialRouterProps()
    {

        $this->props($this->routerProps, 'Illuminate\Routing\Router', new Dispatcher());
    }

    function testOfficialRoutesProps()
    {
        $this->props($this->routesProps, 'Illuminate\Routing\RouteCollection');

    }

    function testOfficialUrlProps()
    {
        $this->props($this->urlProps, 'Illuminate\Routing\UrlGenerator', new RouteCollection(), new Request());

    }

    function props($expect, $class, ...$args)
    {
//        echo "test $class \n";

        switch (count($args)) {
            case 0:
                $obj = new $class();
                break;
            case 1:
                $obj = new $class($args[0]);
                break;
            case 2:
                $obj = new $class($args[0], $args[1]);
                break;
        }
        $reflect = new ReflectionClass($obj);
        $props = $reflect->getProperties();

        foreach ($props as $prop) {
            $actual[] = $prop->getName();
        }

        $this->assertSame(array_diff($expect, $actual), array_diff($actual, $expect));
    }

}