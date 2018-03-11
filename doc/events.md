# two events dispatchers

Laravel has an dispatcher  named 'events' in container,  which is an app events dispatcher for Laravelfly.

There is  another server dispatcher (Symfony\Component\EventDispatcher).

The app dispatcher objects does not created until the Laravel app created.

## server events

1. server.config  
available args: $event['server'], $event['config']
1. server.created  
available args: $event['server'], $event['options']  
the swooler http server wrapped in a Laravelfly server can be got bu $event['server']->getSwoole().
1. worker.starting  
available args: $event['server'], $event['workerid']
1. app.created  
available args: $event['server'], $event['app'], $event['request']  
the $event['request'] is null unless FpmLike is used.
1. worker.ready  
available args: $event['server'], $event['workerid'], $event['app']  
the $event['app'] is null with Mode FpmLike.
1. worker.stopped  
available args: $event['server'] ,$event['workerid'], $event['app']

These events are instances of Symfony\Component\EventDispatcher\GenericEvent, they can be used like
```
$dispatcher = \Laravel\Fly::getDispatcher();

// adding listeners for 'server.config' must be before the server is created.
$this->dispatcher->addListener('server.config', function (GenericEvent $event)  {
    /*
     * if use `$event['options']['worker_num'] =1`,   error:
     *       Indirect modification of overloaded element 
     */
    $options = $event['options'];
    $options['worker_num']=1;
    $event['options'] = $options;
});

$dispatcher->addListener('worker.starting', function (GenericEvent $event) {
    echo "There files can not be hot reloaded, because they are included before worker starting\n";
    var_dump(get_included_files())
});

$dispatcher->addListener('app.created', function (GenericEvent $event) {
    $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
});
```
$dispatcher can also be availableed in a new server class which extends LaravelFly\Server\HttpServer or LaravelFly\Server\FpmHttpServer. 
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