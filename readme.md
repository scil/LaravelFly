
LaravelFly uses LaravelFlyServer(swoole http server based) to run Laravel faster. 

It's a composer package and can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run same laravel project. So, it's easy to try LaravelFly.


## Test

### A simple ab test 

env: 
ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 800M  )
php7.1+opcache for both fpm and laravelfly


item  |  Fly 5 workers[1] | fpm 5 servers[2]  | fpm 5+ servers[3] | Fly 1 worker[4]
------------ | ------------ | ------------- | ------------- | -------------
Time taken for tests | 158.175  | 1630.447  | 3673.403 | 737.869
Failed requests | 0 | 9  | 17 | 3
Total transferred |  2594022  | 2036748  | 2020334 | 2591839 
Requests per second |  12.64  | 1.23  | 0.56  |  2.71
Time per request ( across) |  79.087  | 815.223  |  1836.702  | 368.935
  50% |   103  | 5737 | 8157  | 380
  80% |  177   | 11667 |   15261  |  1267
  99% | 20763  | 44307  | 54928 |  28579
 100%  | 37331  | 59981  | 1574593  |   60137


1. Fly 5 workers:   nginx laravelfly 5workers ( 'worker_num' => 5,  LARAVELFLY_GREEDY = false;)
`ab -n 2000 -c 10 http://127.0.0.1:9502/`
2. fpm 5 servers:  nginx fpm 5servers ( pm=static  pm.max_children=5)
 `ab -n 2000 -c 10 http://127.0.0.1:9588/`
3. fpm 5+ servers:  nginx fpm 5+servers  ( pm = dynamic pm.start_servers = 5 pm.max_children = 50)
 `ab -n 2000 -c 10 http://127.0.0.1:9588/`
4. Fly 1 worker:     nginx  laravelfly 1workers ( 'worker_num' => 1, LARAVELFLY_GREEDY = false;)
`ab -n 2000 -c 10 http://127.0.0.1:9502/ `

Test date : 2017/11

## What

[Swoole](https://github.com/swoole/swoole-src) is an event-based & concurrent tool , written in C, for PHP. The memory allocated in Swoole worker will not be free'd after a request, that can improve preformance a lot.A swoole worker is like a php-fpm worker, every swoole worker is an independent process. When a fatal php error occurs, or a worker is killed by someone, or 'max_request' is handled, the worker would die and a new worker will be created.

LaravelFly loads resources as more as possible before any request. For example , \Illuminate\Foundation\Application instantiates when  a swoole worker start , before any request.

The problem is that, objects which created before request may be changed during a request, and the changes maybe not right for subsequent requests.For example, `app('view')` has a protected property "shared", which is not appropriate to share this property across different requests.

So the key is to backup some objects before any request, and restore them after each request handling has finished.`\LaravelFly\Application` extends `\Illuminate\Foundation\Application` , use method "backUpOnWorker" to backup, and use method "restoreAfterRequest" to restore.

## Debug

Swoole runs in cli mode, so LaravelFly debug is to debug a script 
```
vendor/scil/laravel-fly/bin/laravelfly-server <start|stop|restart>
```

To debug LaravelFly on a remote host such as vagrant, read [Debugging remote CLI with phpstorm](http://www.adayinthelifeof.nl/2012/12/20/debugging-remote-cli-with-phpstorm/?utm_source=tuicool&utm_medium=referral)

## Normal Mode and Greedy Mode

First, let's take a look at `Illuminate\Foundation\Http\Kernel::$bootstrappers`:
```
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];
```
The "$bootstrappers" is what Laravel do before handling a request, LaravelFly execute them before any request, except the last two items "RegisterProviders" and "BootProviders"

In Normal Mode, "RegisterProviders" is placed on "WorkerStart", while "BootProviders" is placed on "request". That means, all providers are registered before any requests and booted after each request.The only exception is, providers in "config('laravelfly.providers_in_request')" are registered and booted after each request.

In Greedy Mode, providers in "config('laravelfly.providers_in_worker')" are registered and booted before any request. Other providers follow Normal Mode rule. 

And In Greedy Mode, you can define which singleton services to made before any request in "config('laravelfly.providers_in_worker')".If necessary, you should define which properties need to backup. 

You can choose Mode in <project_root_dir>/laravelfly.server.php after you publish config files.

In Greedy Mode, response to homepage visit would be 1, 2, 3, 4,.. until current swooler worker reach to server config 'max_request' 
```
// routes/web.php
$a=0;
Route::get('/',function()use(&$a){
    return ++$a;
});
```

Note, Greedy Mode is still experimental.

## Flow

### A Worker Flow in Normal Mode 

* a new worker process
  * create an app 
    * registerBaseServiceProviders(event,log and routing)
  * create a kernel
  * kernel bootstrap
    * LoadEnvironmentVariables LoadConfiguration HandleExceptions
    * **SetProvidersInRequest** see:config/laravelfly 'providers_in_request'
    * RegisterFacades and RegisterProviders
    * **backup**
  * ------ waiting for a request ------
  * ------ when a request arrives ---
  * kernel handle request 
    * **registerConfiguredProvidersInRequest**
    * app->boot
      * fire bootingCallbacks
      * app->booted=true
      * fire bootedCallbacks
    * middleware and router
  * response to client
  * kernel->terminate
    * terminateMiddleware
    * fire app->terminatingCallbacks 
  * **restore**
  * app->booted = false
  * ------ waiting for the 2nd request ------
  * .....(just same as the first request)
  * ------ waiting for the 3ed request ------
  * .....
* the worker process killed when server config 'max_request' reached
* a new worker process
* ......(same as the first worker process).
  


### A Worker Flow in Greedy Mode 

todo

## Install

1. Install php extension [swoole](https://github.com/swoole/swoole-src).  
A simple way is `pecl install swoole`.  
Make sure `extension=swoole.so` in php  config file for cli, not  fpm or apache.
2. `composer require "scil/laravel-fly":"dev-master"`
3. optional:`composer require --dev "eaglewu/swoole-ide-helper:dev-master"` , which is useful in IDE development.

## Config

1. Open terminal and execute `vendor/bin/publish-laravelfly-config-files`  .  
you can add "--force" to overwrite old config files.  
`vendor/bin/publish-laravelfly-config-files --force`
2. Edit `<project_root_dir>/laravelfly.server.config.php`.
3. Edit `<project_root_dir>/config/laravelfly.php`.   
Note: if using Greey Mode, about 'backup and restore', any items prefixed with "/* depends */" need your consideration.
4. Edit `<project_root_dir>/app/Http/Kernel.php`, change `class Kernel extends HttpKernel ` to
```
if (defined('LARAVELFLY_GREEDY')) {
    if (LARAVELFLY_GREEDY) {
        class WhichKernel extends \LaravelFly\Greedy\Kernel { }
    } else {
        class WhichKernel extends \LaravelFly\Kernel { }
    }
} else {
    class WhichKernel extends HttpKernel { }
}

class Kernel extends WhichKernel
```


## Optional Config

* Config and restart nginx: swoole http server lacks some http functions, so it's better to use swoole with other http servers like nginx. There is a nginx site conf example at `vendor/scil/laravel-fly/config/nginx+swoole.conf`.

* if you want to use mysql persistent, add following to config/database.php ( do not worry about "server has gone away", laravel would reconnect it auto)
```
        'options'   => [
            PDO::ATTR_PERSISTENT => true,
        ],
```

## Config examples
* [Debugbar](package_config_examples/Debugbar.md)
* [AsgardCms](package_config_examples/AsgardCms.md)


## Run

Execute 
```
vendor/bin/laravelfly-server start $absolute_path_of_server_config_file
```
Argument `$absolute_path_of_server_config_file` is optional, default is `<project_root_dir>/laravelfly.server.config.php`.

## Stop

Two methods:

* Execute 
```
vendor/bin/laravelfly-server stop $pid_file
```
`$pid_file` is optional, default is `vendor/bin/laravelfly.pid`.which is created by LaravelFlyServer if you not set 'pid_file' for it.

* in php code file, you can make your own swoole http server by extending 'LaravelFlyServer', and use `$this->swoole_http_server->shutdown();` .


## Restart

```
vendor/bin/laravelfly-server restart $pid_file
```
`$pid_file` is optional like above.

## Reload All Workers Gracefully: swoole server reloading

Swoole server has a main process, a manager process and one or more worker processes.If you set `'worker_num' => 4`, there are 6 processes.The first the main process, the second is the manager process, and the last four are all worker processes.

Swoole server reloading has no matter with the main process or the manager process. Swoole server reloading is killing worker processes gracefully and start new.

Gracefully is that: worker willl finish its work before die.

### Two methods to reload
* Execute 
```
vendor/bin/laravelfly-server reload $pid_file
```
Argument `$pid_file` is optional, default is `vendor/bin/laravelfly.pid`. which is created by LaravelFlyServer if you not set 'pid_file' for it.

The work of this script is to send siginal USR1 to swoole manager process. You can run `kill -USR1 PID` in a bash script yourself.

* in php , you can make your own swoole http server by extending 'LaravelFlyServer', and use `$this->swoole_http_server->reload();` under some conditions like some files changed.

### Details:
1. Send USR1 to swoole manager process
2. swoole manager process send TERM to all worker processes
3. Every worker first finish it's work, then call OnWorkerStop callback, then kill itself.
4. manager process creates new worker processes.

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


### Todo

- [ ] memmory and cpu test
- [ ] add tests
- [ ] improve backup and restore
- [ ] Laravel5.5, like package auto-detection
- [ ] send file
- [ ] try to add Providers with concurrent services, like mysql , redis;  add cache to Log
