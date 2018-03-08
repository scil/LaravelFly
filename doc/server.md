
## Start

Execute 
```
php vendor/scil/laravel-fly/bin/fly start [$server_config_file]
```
Argument `$server_config_file` is optional, default is `<project_root_dir>/fly.conf.php`.

You can make multiple config files which have different listen_port, then you can run multiple server.

Note: LaravelFly will not supply an artisan command to run server, for the sake of less memory usage.

## Stop

Two methods:

* Execute 
```
php vendor/scil/laravel-fly/bin/fly stop [$server_config_file]
```

* in php code file, you can make your own swoole http server by extending 'LaravelFly\Server\HttpServer', and use `$this->server->shutdown();` .


## Restart

```
php vendor/scil/laravel-fly/bin/fly restart [$server_config_file]
```


## Debug

LaravelFlyServer runs in cli mode, so LaravelFly debug is to debug a script 
```
php vendor/scil/laravel-fly/bin/fly <start|stop|restart>
```

To debug LaravelFly on a remote host such as vagrant, read [Debugging remote CLI with phpstorm](http://www.adayinthelifeof.nl/2012/12/20/debugging-remote-cli-with-phpstorm/?utm_source=tuicool&utm_medium=referral) then use a command like this:
```
php -dxdebug.remote_host=192.168.1.2  vendor/scil/laravel-fly/bin/fly <start|stop|restart>
```
replace 192.168.1.2 with your ip where phpstorm is.

### About XDebug
composer update/require may slow when enable XDebug in CLI environment


## Reload All Workers Gracefully: swoole server reloading

Swoole server has a main process, a manager process and one or more worker processes.If you set `'worker_num' => 4`, there are 6 processes.The first the main process, the second is the manager process, and the last four are all worker processes.

Swoole server reloading has no matter with the main process or the manager process. Swoole server reloading is killing worker processes gracefully and start new.

Gracefully is that: worker willl finish its work before die.

### Two methods to reload
* Execute 
```
php vendor/scil/laravel-fly/bin/fly reload [$server_config_file]
```

* in php , you can make your own swoole http server by extending 'LaravelFly\Server\HttpServer', and use `$this->server->reload();` under some conditions like some files changed.

## Hot Reload On Code Change

By using swoole server reloading, it's possible to hot reload on code change, because any files required or included in 'WorkerStart' callback will be requied or included again when a new worker starts.

Note, files required or included before 'WorkerStart' will keep in memory, even swoole server reloads.

So it's better to include/require files which change rarely before 'WorkerStart' to save memory, to include/require files which change often in 'WorkerStart' callback to hot reload.

You could moniter some files and reload server(two methods above) , just make sure there files are required/included in 'WorkerStart' callback.

If you use APC/OpCache, you could use one of these measures
* edit php.ini and make APC/OpCache to hot reload opcode
* edit swoole server code:
```
  function onWorkerStop($serv, $worker_id) {
       opcache_reset(); // opcache reset function, use similar function if you use APC
  }
```

