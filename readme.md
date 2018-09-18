LaravelFly speeds up our existing Laravel projects without data pollution and memory leak, and make Tinker to be used online (use tinker while Laravel is responding requests from browsers).

Thanks to [Laravel](http://laravel.com/), [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## A simple ab test 

 `ab -k -n 1000 -c 10 http://zc.test

.   | fpm  | Fly
------------ | ------------ | ------------- 
Requests per second   | 3    | 34
Time taken ≈ | 325  | 30
  50%  | 2538  | 126
  80%  | 3213  | 187
  99%  | 38584 | 3903

<details>
<summary>Test Env</summary>
<div>


* A visit to http://zc.test relates to 5 Models and 5 db query.
* env:   
  - ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 1G  )  
  - php7.1 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)
  - 'max_conn' => 1024
* Test date : 2018/02

</div>
</details>

## Version Compatibility

- Laravel 5.5 or 5.6
- Swoole >4.0

## Quick Start

1.`pecl install swoole`   
Make sure `extension=swoole.so` in config file for php cli.   
Suggest: `pecl install inotify`   

2.`composer require "scil/laravel-fly":"dev-master"`

3.`php vendor/scil/laravel-fly/bin/fly start`   
If you enable `eval(tinker())` and see an error about mkdir, you can start LaravelFly with sudo.

Now, your project is flying and listening to port 9501. Enjoy yourself.

## Doc

[Configuration](https://github.com/scil/LaravelFly/wiki/Configuration)

[Commands: Start, Reload & Debug](https://github.com/scil/LaravelFly/wiki/Commands)

[Coding Guideline](https://github.com/scil/LaravelFly/wiki/Coding-Requirement)

[Events about LaravelFly](doc/events.md)

[Using tinker when Laravel Working](doc/tinker.md)

[For Dev](doc/dev.md)

## Features

- Same codes can run on PHP-FPM or LaravelFly

- To be absolutely safe, put your code under control. Coroutine is supported (code execution can jump from one request to another).

- The majority of Laravel services or some other objects can be made before any requests. There are two types:
  - be configurable to serve in multiple requests (only one instance of the service). LaravelFly named it  **WORKER SERVICE**, **WORKER OBJECT** or **COROUTINE-FRIENDLY SERVICE/OBJECT**.
  - to be cloned in each request (one instance in one request).LaravelFly named it **CLONE SERVICE** or **CLONE OBJECT**. This way is simple, but often has the problem [Stale Reference](https://github.com/scil/LaravelFly/wiki/clone-and-Stale-Reference). This type is used widely by [laravel-swoole](https://github.com/swooletw/laravel-swoole) and [laravel-s](https://github.com/hhxsv5/laravel-s),  while used rarely by LaravelFly.
  
- Extra speed improvements such as middlewares cache, view path cache.

- Check server info at /laravel-fly/info. It's better to view json response in Firefox, instead of Chrome or IE. (This feture is under dev and more infomations will be available.)

- No support for static files, so use it with other servers like nginx. [conf examples](https://github.com/scil/LaravelFly/#laravelfly-usability)

- functions `fly()` and `fly2()` which are like `go()` provided by [golang](https://github.com/golang/go) or [swoole](https://github.com/swoole/swoole-src), but Laravel services are be used in `fly()` and `fly2()`.  The `fly2()` has the ability to change services of current request, e.g. registering a new event handler for current request.

A coroutine starting in a request, can still live when the request ends. What's the effect of following route?    
It responds with 'coroutine1; outer1; coroutine2; outer2; outer3',   
but it write log 'coroutine1; outer1; coroutine2; outer2; outer3; coroutine2.end; coroutine1.end'
``` 

Route::get('/fly', function () {

    $a = [];
    
    fly(function () use (&$a) {
        $a[] = 'coroutine1';
        \co::sleep(2);
        $a[] = 'coroutine1.end';
        \Log::info(implode('; ', $a));
    });

    $a[] = 'outer1';

    go(function () use (&$a) {
        $a[] = 'coroutine2';
        \co::sleep(1.2);
        $a[] = 'coroutine2.end';
    });

    $a[] = 'outer2';

    \co::sleep(1);

    $a[] = 'outer3';

    return implode(';', $a);

});
```

## LaravelFly Usability 

It can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run the same laravel project.

The nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) allow you use LaravelFlyServer as the primary server, and the phpfpm as a backup server which will be passed requests when the LaravelFlyServer is unavailable. .

Another nginx conf [use_swoole_or_fpm_depending_on_clients](config/use_swoole_or_fpm_depending_on_clients.conf) allows us use query string `?useserver=<swoole|fpm|...` to select the server between swoole or fpm. That's wonderful for test, such as to use eval(tinker()) as a online debugger for your fpm-supported projects.

## Similar projects that mix swoole and laravel

### 1. [laravel-swoole](https://github.com/swooletw/laravel-swoole) 

It is alse a safe sollution. It is light.It has supported Lumen and websocket. Its doc is great and also useful for LaravelFly.   

The main difference is that in laravel-swoole user's code will be processed by a new `app` cloned from SwooleTW\Http\Server\Application::$application and laravel-swoole updates related container bindings to the new app. However in LaravelFly, the sandbox is not a new app, but an item in the $corDict of the unique application container. In LaravelFly, most other objects such as `app`, `event`.... always keep one object in a worker process, `clone` is used only to create `url` by default. LaravelFly makes most of laravel objects keep safe on their own. It's about high cohesion & low coupling and the granularity is at the level of app container or services/objects. For users of laravel-swoole, it's a big challenge to handle the relations of multiple packages and objects which to be booted before any requests. Read [Stale Reference](https://github.com/scil/LaravelFly/wiki/clone-and-Stale-Reference). 

 .  | speed |technique | every service is in control |  every service provider is in control | work to maintaining relations of cloned objects to avoid Stale Reference 
------------ |------------ | ------------ | ------------- | ------------- | ------------- 
laravel-swoole  | slow | clone app contaniner and objects to make them safe |  yes | no | more work (app,event...are cloned)
LaravelFly Mode Map | fast | refactor most official objects to make them safe on their own |  yes  | yes  | few work (only url is cloned by default)

### 2. [laravel-s](https://github.com/hhxsv5/laravel-s)

Many great features!

About data pollution? Same technique and problems as laravel-swoole. And neither support coroutine jumping (from one request to another request). 


## Todo About Improvement

- [x] Pre-include. Server configs 'pre_include' and 'pre_files'.
- [x] Server config 'early_laravel'
- [x] Cache for LaravelFly app config. laravelfly_ps_map.php or laravelfly_ps_simple.php located bootstrap/cache
- [x] Cache for Log. Server options 'log_cache'.
- [x] Watching maintenance mode using swoole_event_add. No need to check file storage/framework/down in every request.
- [x] Cache for kernel middlewares objects. Kernel::getParsedKernelMiddlewares, only when LARAVELFLY_SERVICES['kernel'] is true.
- [x] Cache for route middlewares. $cacheByRoute in Router::gatherRouteMiddleware, only useful when all route middleaes are reg on worker.
- [x] Cache for route middlewares objects. config('laravelfly.singleton_route_middlewares') and $cacheForObj in Router::gatherRouteMiddleware, avoid creating instances repeatly.
- [x] Cache for terminateMiddleware objects.
- [x] Cache for event listeners. $listenersStalbe in LaravelFly\Map\IlluminateBase\Dispatcher
- [x] Cache for view compiled path. LARAVELFLY_SERVICES['view.finder'] or  App config 'view_compile_1'
- [x] Mysql coroutine. Old code dropped, laravel-s used.
- [ ] Mysql connection pool
- [ ] event: wildcardsCache? keep in memory，no clean?
- [ ] Converting between swoole request/response and Laravel Request/Response
- [ ] safe: auth, remove some props?

## Other Todo

- [x] add events
- [x] watch code changes and hot reload
- [x] supply server info. default url is: /laravel-fly/info
- [x] function fly()
- [ ] add tests about auth SessionGuard: Illuminate/Auth/SessionGuard.php with uses Request::createFromGlobals
- [ ] add tests about uploaded file, related symfony/http-foundation files: File/UploadedFile.php  and FileBag.php(fixPhpFilesArray)
- [ ] websocket
- [ ] send file
- [ ] travis, static analyze like phan, phpstan or https://github.com/exakat/php-static-analysis-tools
- [ ] decrease worker ready time
- [ ] cache fly

