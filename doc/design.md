# Key concept: swoole worker.

[Swoole](https://github.com/swoole/swoole-src) is an event-based tool. The memory allocated in Swoole worker will not be free'd after a request. A swoole worker is like a php-fpm worker, every swoole worker is an independent process. When a fatal php error occurs, or a worker is killed by someone, or 'max_request' is handled, the worker would first finish its work then die, and a new worker will be created.

Laravel's services/resources can be loaed following the start of swoole server or swoole worker.

# Design

## 1. Laravel application is created `onWorkerStart`

This means: There's an application in each worker process. When a new worker starts, a new application is made.

Goods:
* Hot Reload On Code Change. You can reload LaravelFly server manually or automatically with files monitor.
* After a worker has handled 'max_request' requests, it will stop and a new worker starts.Maybe it  helps set aside suspicions that php can't run long time.

BTW, in Mode FpmLike, all objects are loaded onRequest, like php-fpm. Mode FpmLike does nothing except converting swoole request to laravel request and laravel reponse to swoole response.It just provides a opportunity to use tinker() or similar shells online.

## 2. Load services `onWorkerStart` as many as possbile?

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

In Mode Hash or Mode Greedy, providers in "config('laravelfly.providers_on_worker')" are registered and booted before any request. Other providers follow Mode Simple rule. 

And In Mode Hash or Mode Greedy, you can define which singleton services to made before any request in "config('laravelfly.providers_in_worker')".

In Mode Greedy, response to homepage visit would be 1, 2, 3, 4,.. until current swooler worker reach to server config 'max_request' 
```
// routes/web.php
$a=0;
Route::get('/',function()use(&$a){
    return ++$a;
});
```
Note, Mode Greedy is still experimental and only for study.

Mode Hash is under dev and is future.

## Challenge: data pollution

Objects which created before request may be changed during a request, and the changes maybe not right for subsequent requests.For example, a event registered in a request will persist in subsequent requests. Second example, `app('view')` has a protected property "shared", which sometime is not appropriate to share this property across different requests.

Global variables and static members have similar problems.

Mode Hash is more complicated than Mode Simple or Greedy. Requests are not handled one by one.

There are three solutions..

The first is to backup some objects before any request, and restore them after each request .`\LaravelFly\Application` extends `\Illuminate\Foundation\Application` , use method "backUpOnWorker" to backup, and use method "restoreAfterRequest" to restore.This method is call Mode Simple or Mode Greedy. Mode Simple only handle laravel's key objects, such as app, event. Mode Greedy tries to load services as many as possible, such as db, cache. Note: Mode Greedy is only for study.
 
The first solution can not use swoole's coroutine.

The second is to clone or create new objects such as app/event/.. for each request. 

The third is to refactor laravel's services, moving related members to a new associative array with coroutine id as keys. This method is called Mode Hash as it uses swoole coroutine.This mode is under dev.

#  Minor improvement
