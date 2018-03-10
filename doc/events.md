# two events dispatchers

Laravel has an dispatcher  named 'events' in container,  which is an app events dispatcher for Laravelfly.

There is  another server dispatcher (Symfony\Component\EventDispatcher).

The app dispatcher objects does not created until the Laravel app created.

## server events

1. server.config  
access args: $event['server'], $event['config']
1. server.created  
access args: $event['server'], $event['swoole'], $event['options']  
$event['swoole'] is the swooler http server wrapped in a Laravelfly server.
1. worker.starting
access args: $event['server'], $event['workerid']
1. app.created
access args: $event['server'], $event['app'], $event['request']  
the $event['request'] is null unless FpmLike is used.
1. worker.ready
access args: $event['server'], $event['workerid']
1. worker.stopped
access args: $event['server'] ,$event['workerid']

These events are instances of Symfony\Component\EventDispatcher\GenericEvent, they can be used like
```
$dispatcher = \Laravel\Fly::getDispatcher();
$dispatcher->addListener('app.created', function (GenericEvent $event) {
    $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
});
```
$dispatcher can also be accessed in a new server class which extends LaravelFly\Server\HttpServer or LaravelFly\Server\FpmHttpServer. 
```
class MyServer extends \LaravelFly\Server\HttpServer
{
    public function start()
    {
        $this->dispatcher->addListener('app.created', function (GenericEvent $event) {
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


## app events
//todo