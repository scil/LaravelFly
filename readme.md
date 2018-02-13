Tinker can be used online and laravel can be much faster by LaravelFly.

## Tinker online

```php
Route::get('hi', function () {
    $friends = 'Tinker';

    if (starts_with(Request::ip(), ['192.168', '127'])) {
        eval(tinker());
    }

    return view('fly', compact('friends'));
});
```

blade file for view('fly') 

```blade.php
@php(eval(tinker()))

@foreach($friends as $one)

    <p>Hello, {!! $one !!}!</p>

@endforeach
```

Visit '/hi' from localhost, you can enter tinker shell like this: 
[![tinker()](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD.png)](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD?t=3)
The tinker() demo to use vars, change vars, use Log::info and so on.

## Usability 

It's a composer package and can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run same laravel project.

There is a nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) which let you use LaravelFlyServer as the primary server, and the phpfpm as a backup tool which will be passed requests when the LaravelFlyServer is unavailable. .

## Speed Test

### A simple ab test 

env:   
ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 1G  )  
php7.1 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)

`ab -k -n 1000 -c 10 http://zhenc.test/green `

item   | fpm |  Fly Simple | Fly Coroutine
------------ | ------------ | ------------- | ------------- 
Time taken for tests | 325 | 193.44  | 29.17
Requests per second   | 3.08|  5.17  | 34.28
  50%  | 2538|   167  | 126
  80%  |   3213|  383   | 187
  99%   | 38584| 33720  | 3903

Test date : 2018/02

## Key concept: swoole worker.

[Swoole](https://github.com/swoole/swoole-src) is an event-based tool. The memory allocated in Swoole worker will not be free'd after a request. A swoole worker is like a php-fpm worker, every swoole worker is an independent process. When a fatal php error occurs, or a worker is killed by someone, or 'max_request' is handled, the worker would first finish its work then die, and a new worker will be created.

Laravel's services/resources can be loaed following the start of swoole server or swoole worker.

## Design For Speed 

### 1. Laravel application is created `onWorkerStart`

This means: There's an application in each worker process. When a new worker starts, a new application is made.

Goods:
* Hot Reload On Code Change. You can reload LaravelFly server manually or automatically with files monitor.
* After a worker has handled 'max_request' requests, it will stop and a new worker starts.Maybe it  helps set aside suspicions that php can't run long time.

BTW, in Mode FpmLike, all objects are loaded onRequest, like php-fpm. Mode FpmLike does nothing except converting swoole request to laravel request and laravel reponse to swoole response.It just provides a opportunity to use tinker() or similar shells online.

### 2. Load services `onWorkerStart` as many as possbile?

Before handling a request, laravel does much work.

Let's take a look at `Illuminate\Foundation\Http\Kernel::$bootstrappers`:
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

In Mode Simple, "RegisterProviders" is placed on "WorkerStart", while "BootProviders" is placed on "request". That means, all providers are registered before any requests and booted after each request.The only exception is, providers in "config('laravelfly.providers_in_request')" are registered and booted after each request.

In Mode Coroutine or Mode Greedy, providers in "config('laravelfly.providers_on_worker')" are registered and booted before any request. Other providers follow Mode Simple rule. 

And In Mode Coroutine or Mode Greedy, you can define which singleton services to made before any request in "config('laravelfly.providers_in_worker')".

In Mode Greedy, response to homepage visit would be 1, 2, 3, 4,.. until current swooler worker reach to server config 'max_request' 
```
// routes/web.php
$a=0;
Route::get('/',function()use(&$a){
    return ++$a;
});
```
Note, Mode Greedy is still experimental and only for study.

Mode Coroutine is under dev and is future.

## Challenge: data pollution

Objects which created before request may be changed during a request, and the changes maybe not right for subsequent requests.For example, a event registered in a request will persist in subsequent requests. Second example, `app('view')` has a protected property "shared", which sometime is not appropriate to share this property across different requests.

Global variables and static members have similar problems.

Mode Coroutine is more complicated than Mode Simple or Greedy. Requests are not handled one by one.

There are three solutions..

The first is to backup some objects before any request, and restore them after each request .`\LaravelFly\Application` extends `\Illuminate\Foundation\Application` , use method "backUpOnWorker" to backup, and use method "restoreAfterRequest" to restore.This method is call Mode Simple or Mode Greedy. Mode Simple only handle laravel's key objects, such as app, event. Mode Greedy tries to load services as many as possible, such as db, cache. Note: Mode Greedy is only for study.
 
The first solution can not use swoole's coroutine.

The second is to clone or create new objects such as app/event/.. for each request. 

The third is to refactor laravel's services, moving related members to a new associative array with coroutine id as keys. This method is called Mode Coroutine as it uses swoole coroutine.This mode is under dev.

## Mode Simple vs Mode Coroutine

features  |  Mode Simple | Mode Coroutine 
------------ | ------------ | ------------- 
global vars like $_GET, $_POST | yes  | no
coroutine| no  | yes (conditional*)

## php functions not fit Swoole/LaravelFly

name | replacement
------------ | ------------ 
header | Laravel api: $response->header
setcookie | Laravel api: $response->cookie

## Similar projects

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: like LaravelFly, laravoole loads app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as event registering.

## Install

1. Install php extension [swoole](https://github.com/swoole/swoole-src).  
A simple way is `pecl install swoole`.  
Make sure `extension=swoole.so` in php cli config file, not  fpm or apache.
2. composer require "scil/laravel-fly":"dev-master"`
3. optional:`composer require --dev "eaglewu/swoole-ide-helper:dev-master"` , which is useful in IDE development.

## Config

1. Execute `php artisan vendor:publish --tag=fly-server`  .  
2. Edit server config file `<project_root_dir>/laravelfly.php`.
3. If you use tinker(), put this line at the top of `public/index.php` :  
` function tinker(){ return '';} `  
This line avoids error `Call to undefined function tinker()`  when you use php-fpm with tinker() in your code.

4. Edit `<project_root_dir>/app/Http/Kernel.php`, change `class Kernel extends HttpKernel ` to
```
if (defined('LARAVELFLY_MODE')) {
    if (LARAVELFLY_MODE == 'Coroutine') {
        class WhichKernel extends \LaravelFly\Coroutine\Kernel { }
    }elseif (LARAVELFLY_MODE == 'Simple') {
        class WhichKernel extends \LaravelFly\Simple\Kernel { }
    } elseif (LARAVELFLY_MODE == 'FpmLike') {
        class WhichKernel extends HttpKernel{}
    } else {
        class WhichKernel extends \LaravelFly\Greedy\Kernel { }
    }
} else {
    class WhichKernel extends HttpKernel { }
}

class Kernel extends WhichKernel
```

## Optional Config

* Config and restart nginx: swoole http server lacks some http functions, so it's better to use swoole with other http servers like nginx. There is a nginx site conf example at `vendor/scil/laravel-fly/config/nginx+swoole.conf`.

* if you want to use mysql persistent, add following to config/database.php
```
'options' => [
    PDO::ATTR_PERSISTENT => true,
],
```
* In Mode Coroutine,coroutine can be used for mysql. Please compile swoole with  --enable-coroutine, then disable xdebug, xhprof, or blackfire. Yes, currently, swoole not compatible with them. Third, add `'coroutine' => true,` to config/database. This feature is still under dev.And you must disable xdebug or similar library.
```
'mysql' => [
    'driver' => 'mysql',
    'coroutine' => true,
],
```
* Execute `php artisan vendor:publish --tag=fly-app`  and edit `<project_root_dir>/config/laravelfly.php`.   
Note: items prefixed with "/** depends " deverve your consideration.

## Config examples for Third Party Serivce
* [Debugbar](package_config_examples/Debugbar.md)
* [AsgardCms](package_config_examples/AsgardCms.md)


## Run

Execute 
```
vendor/bin/laravelfly start [$server_config_file]
```
Argument `$server_config_file` is optional, default is `<project_root_dir>/laravelfly..php`.

You can make multiple config files which have different listen_port, then you can run multiple server.

Note: LaravelFly will not supply an artisan command to run server, for the sake of less memory usage.

## Stop

Two methods:

* Execute 
```
vendor/bin/laravelfly stop [$server_config_file]
```

* in php code file, you can make your own swoole http server by extending 'LaravelFlyServer', and use `$this->swoole_http_server->shutdown();` .


## Restart

```
vendor/bin/laravelfly restart [$server_config_file]
```


## Debug

LaravelFlyServer runs in cli mode, so LaravelFly debug is to debug a script 
```
php vendor/scil/laravel-fly/bin/laravelfly <start|stop|restart>
```

To debug LaravelFly on a remote host such as vagrant, read [Debugging remote CLI with phpstorm](http://www.adayinthelifeof.nl/2012/12/20/debugging-remote-cli-with-phpstorm/?utm_source=tuicool&utm_medium=referral) then use a command like this:
```
php -dxdebug.remote_host=192.168.1.2  vendor/scil/laravel-fly/bin/laravelfly <start|stop|restart>
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
vendor/bin/laravelfly reload [$server_config_file]
```

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

- [ ] add tests
- [ ] Laravel5.5, like package auto-detection
- [ ] send file
- [ ] try to add Providers with concurrent services, like mysql , redis;  add cache to Log

## Flow

### A Worker Flow in Mode Simple 

* a new worker process
  * create an app 
    * registerBaseServiceProviders(event,log and routing)
  * create a kernel
  * kernel bootstrap
    * LoadEnvironmentVariables LoadConfiguration HandleExceptions
    * **CleanProviders** see:config/laravelfly 'providers_in_request'
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
  


### A Worker Flow in Coroutine Mode 

todo
