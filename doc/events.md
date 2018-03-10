# two events dispatchers

Laravel has an dispatcher  named 'events' in container,  which is an app events dispatcher for Laravelfly.

There is  another server dispatcher (Symfony\Component\EventDispatcher).

The app dispatcher objects does not created until the Laravel app created.

## server events

1. name: server.config  
args: server, config
1. name: server.created  
args: server, swoole, options
1. name: worker.starting
args: server, workerid
1. name: app.created
args: server, app, request
1. name: worker.stopped

These events are instances of Symfony\Component\EventDispatcher\GenericEvent, they can be used like
```
$dispatcher = \Laravel\Fly::getDispatcher();
$dispatcher->addListener('app.created', function (GenericEvent $event) {
    $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
});
```
$dispatcher can also be accessed in a new server class which extends LaravelFly\Server\HttpServer or LaravelFly\Server\FpmHttpServer. Just put the new class in a server config file.
```
    'server' => \LaravelFly\Server\HttpServer::class,
```


## app events
//todo