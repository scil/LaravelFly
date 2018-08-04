<?php
/**
 * User: scil
 * Date: 2018/6/30
 * Time: 22:15
 */

namespace LaravelFly\Tests\Backup\Unit;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use LaravelFly\Tests\BaseTestCase;
use Mockery\Exception;
use ReflectionClass;
use ReflectionProperty;

class PropsTest extends BaseTestCase
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

    var $redirectorProps = [
        'generator',
        'session',
        'macros',
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

        $this->props($this->routerProps, 'Illuminate\Routing\Router', Dispatcher::class);
    }

    function testOfficialRoutesProps()
    {
        $this->props($this->routesProps, 'Illuminate\Routing\RouteCollection');

    }

    function testOfficialUrlProps()
    {
        $this->props($this->urlProps, 'Illuminate\Routing\UrlGenerator', RouteCollection::class, Request::class);

    }

    function testOfficialRedirectorProps()
    {
        $this->props($this->redirectorProps, 'Illuminate\Routing\Redirector',
            // new UrlGenerator(new RouteCollection(), new Request())
            [
                UrlGenerator::class => [
                    RouteCollection::class,
                    Request::class
                ]
            ]
        );

    }

    function props($expect, $class, ...$args)
    {
        $actual = $this->getPropsInProcess($class, $args);

        $this->assertSame(array_diff($expect, $actual), array_diff($actual, $expect),"please edit Backup\Application or config('laravelfly.BaseServices')");
    }

    function getPropsInProcess($class, $args)
    {
        return self::processGetArray(function () use ($class, $args) {
//        echo "test $class \n";

            switch (count($args)) {
                case 0:
                    $obj = new $class();
                    break;
                case 1:
                    $obj = new $class($this->parseArg($args[0]));
                    break;
                case 2:
                    $obj = new $class($this->parseArg($args[0]), $this->parseArg($args[1]));
                    break;
                default:
                    return "too many arguments? ";
            }
            $reflect = new ReflectionClass($obj);
            $props = $reflect->getProperties();

            foreach ($props as $prop) {
                $actual[] = $prop->getName();
            }

            return $actual;

        });
    }

    function parseArg($arg)
    {
        if (is_array($arg)) {
            foreach ($arg as $class => $args) {
                switch (count($args)) {
                    case 1:
                        return new $class($this->parseArg($args[0]));
                    case 2:
                        return new $class($this->parseArg($args[0]), $this->parseArg($args[1]));
                    default:
                        throw new \Exception('too many argumnets?');

                }
            }
        }

        return is_string($arg) && class_exists($arg) ? new $arg : $arg;
    }

}