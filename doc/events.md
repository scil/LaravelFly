# two events dispatchers

Laravel has an dispatcher  named 'events' in container,  which is an app events dispatcher for Laravelfly.

There is  another server dispatcher (Symfony\Component\EventDispatcher).

The app dispatcher objects does not created until the Laravel app created.

## events and callbacks before the first request or after the last request

1. worker.starting  
available args: $event['server'], $event['workerid']
the swooler http server wrapped in a Laravelfly server can be got by $event['server']->getSwoole().
1. laravel.ready  
available args: $event['server'], $event['app'], $event['request']  
the $event['request'] is null unless FpmLike is used.
1. events 'bootstrapping' and events 'bootstrapped' of multiple Bootstrap classes listed in your kernel class  
In Mode Map, callbacks bootingCallbacks are called after the event 'bootstrapping: LaravelFly\Map\Bootstrap\RegisterAndBootProvidersOnWork', before the event 'bootstrapped: LaravelFly\Map\Bootstrap\RegisterAndBootProvidersOnWork'
1. worker.ready  
available args: $event['server'], $event['workerid'], $event['app']  
the $event['app'] is null with Mode FpmLike.  
In Mode Simple or Map, this event may look like 'PHP_MINIT_FUNCTION' in php ext, or 'before_first_request' in python Flask.
1. worker.stopped  
available args: $event['server'] ,$event['workerid'], $event['app']

These events are instances of Symfony\Component\EventDispatcher\GenericEvent, they can be used like
```
$dispatcher = \LaravelFly\Fly::getServer()->getDispatcher();

$dispatcher->addListener('worker.starting', function (GenericEvent $event) {
    echo "There files can not be hot reloaded, because they are included before worker starting\n";
    var_dump(get_included_files())
});

$dispatcher->addListener('laravel.ready', function (GenericEvent $event) {
    $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
});
```
$dispatcher can also be available in a new server class which extends LaravelFly\Server\HttpServer or LaravelFly\Server\FpmHttpServer. 
```
class MyServer extends \LaravelFly\Server\HttpServer
{
    public function start()
    {
        $this->dispatcher->addListener('laravel.ready', function (GenericEvent $event) {
            $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
        });

        parent::start();
    }

}
```
Put the new class in a server config file.
```
    'server' => MyServer::class,
```


## some events and callbacks in a request in Mode Map

1. request.corinit  
1. bootedCallbacks (callbacks provided by laravel)  
register a callback: app()->booted($callback)
1. Illuminate\Routing\Events\RouteMatched  (provided by laravel)
1. Illuminate\Foundation\Http\Events\RequestHandled  (provided by laravel)
1. terminatingCallbacks (callbacks provided by laravel)  
register a callback: app()->terminating($callback)
1. request.corunset


##  some events and callbacks in a request in Mode Simple

1. bootingCallbacks (callbacks provided by laravel)  
1. bootedCallbacks (callbacks provided by laravel)  
1. Illuminate\Routing\Events\RouteMatched
1. Illuminate\Foundation\Http\Events\RequestHandled
1. terminatingCallbacks (callbacks provided by laravel)  
 