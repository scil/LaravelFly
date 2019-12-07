[![996.icu](https://img.shields.io/badge/link-996.icu-red.svg)](https://996.icu)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)

Would you like php 7.4 Preloading? Would you like php coroutine? Today you can use them with Laravel  because of Swoole.

LaravelFly is a safe solution to speeds up new or old Laravel 5.5+ projects, with preloading and coroutine, while without data pollution or memory leak. And it makes Tinker available online (use tinker while Laravel is responding requests from browsers).

Thanks to [Laravel](http://laravel.com/), [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh).

## A simple ab test 

`ab -k -n 1000 -c 10 http://zc.test` (21 sql statements were executed in single request)

.   | fpm  | Fly
------------ | ------------ | ------------- 
Time taken ≈ | 43.5 s  | 12.3 s
Requests per second   | 23    | 81.5
  50%  | 303 ms  | 117 ms
  80%  | 360 ms  | 153 ms
  99%  | 1341 ms | 239 ms

<details>
<summary>Test Env</summary>
<div>


* env:   
  - ubuntu 16.04 on VirtualBox ( 1 CPU: i7-7700HQ 2.80GHz ; Memory: 2G  )  
  - php7.2 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)
  - connection pool and coroutine mysql
* Test date : 2018/10

</div>
</details>

## Version Compatibility

- Laravel 5.5 ~ 6.0
- Swoole >4.2.13

## Quick Start

1.`pecl install swoole`   
Make sure `extension=swoole.so` in config file for php cli, not for fpm.   
Suggest: `pecl install inotify`   

2.`composer require "scil/laravel-fly":"dev-master"`

3.`php vendor/scil/laravel-fly/bin/fly start`   
If you enable `eval(tinker())` and see an error about mkdir, you can start LaravelFly with sudo.

Now, your project is flying and listening to port 9501. Enjoy yourself.

## Docker-compose

```
composer require "scil/laravel-fly":"dev-master"

php artisan vendor:publish --tag=fly-server

# 127.0.0.1:8080
# you can edit this docker-compos file and use your own nginx conf
docker-compose -f vendor/scil/laravel-fly-local/docker/docker-compose.yml up -d
```

## Doc

[Configuration](https://github.com/scil/LaravelFly/wiki/Configuration)

[Commands: Start, Reload & Debug](https://github.com/scil/LaravelFly/wiki/Commands)

[Coding Guideline](https://github.com/scil/LaravelFly/wiki/Coding-Requirement)

[Events about LaravelFly](doc/events.md)

[Using tinker when Laravel Working](doc/tinker.md)

[For Dev](doc/dev.md)

## Recommended Packages

- [swlib/saber](https://github.com/swlib/saber/blob/master/README-EN.md)  Coroutine HTTP client, based on `Swoole\Coroutine\Http\Client`.   
Browser-like cookie managment, multiple requests concurrent, request/response interceptors and so on.  
To ensure safety, set `const LARAVELFLY_COROUTINE = true;` in fly.conf.php.


## Features and Behaviors

- Same codes can run on PHP-FPM or LaravelFly. LaravelFly can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run the same laravel project. The nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) allow you use LaravelFlyServer as the primary server, and the phpfpm as a backup server which will be passed requests when the LaravelFlyServer is unavailable. Another nginx conf [use_swoole_or_fpm_depending_on_clients](config/use_swoole_or_fpm_depending_on_clients.conf) allows us use query string `?useserver=<swoole|fpm|...` to select the server between swoole or fpm. That's wonderful for test, such as to use eval(tinker()) as a online debugger for your fpm-supported projects. Apache? There is a example [Cooperate with Apache](https://github.com/hhxsv5/laravel-s#cooperate-with-apache) from laravel-s.

- Moderate strategy: by default, each Third Party service provider is registered on server worker process (before the first request arrived at server) , booted in request.

- By default, all Laravel official services are COROUTINE-FRIENDLY, including mysql and redis. You can make a service or object before any requests. There are two ways:
  - let the service or object live in multiple requests (only one instance of the service). LaravelFly named it  **WORKER SERVICE**, **WORKER OBJECT** or **COROUTINE-FRIENDLY SERVICE/OBJECT**.
  - cloned the service or object in each request (one instance in one request).LaravelFly named it **CLONE SERVICE** or **CLONE OBJECT**. This way is simple, but often has the problem [Stale Reference](https://github.com/scil/LaravelFly/wiki/clone-and-Stale-Reference). This type is used widely by [laravel-swoole](https://github.com/swooletw/laravel-swoole) and [laravel-s](https://github.com/hhxsv5/laravel-s),  while used rarely by LaravelFly.
  
- Extra speed improvements such as connection pool, middlewares cache, view path cache.

- Check server info at /laravel-fly/info. (This feture is under dev and more infomations will be available.)

- No support for static files any more, so use it with other servers. Conf examples: [nginx](https://github.com/scil/LaravelFly/#laravelfly-usability) or [Apache](https://github.com/hhxsv5/laravel-s#cooperate-with-apache)

- [swoole-job](https://github.com/scil/LaravelFly/wiki/Configuration#run-jobs-or-listeners-in-swoole-tasks),A Laravel job or event listener can be delivered into a swoole task process and executed at once `artisan queue:work` needless any more.

- `exit()` or `die()` in an route action would output content to console or swoole log, and not make server die or reload. If you would like to change that behavior, fork LaravalFly and catch `\Swoole\ExitException` in  `LaravelFly\Map\Kernel::handle`.

- Support connection pool. There's only one connection available in each request.But if you use `fly()` or `fly2()`, you can use connections more than one.

- functions `fly()` and `fly2()` which are like `go()` provided by [golang](https://github.com/golang/go) or [swoole](https://github.com/swoole/swoole-src), plus Laravel services can be used in `fly()` and `fly2()` without closure.  The `fly2()` has the limited ability to change services in current request, e.g. registering a new event handler for current request. `fly2()` is not suggested. 

A coroutine starting in a request, can still live when the request ends. What's the effect of following route?    
It responds with 'coroutine1; outer1; coroutine2; outer2; outer3',   
but it write log 'coroutine1; outer1; coroutine2; outer2; outer3; coroutine2.end; coroutine1.end'
``` 
// ensure ` const LARAVELFLY_COROUTINE = true;` in fly.conf.php

Route::get('/fly', function () {

    $a = [];
    
    fly(function () use (&$a) {
    
        $a[] = 'coroutine1';
        \co::sleep(2);
        $a[] = 'coroutine1.end';
        \Log::info(implode('; ', $a));
        
        // Eloquent can be used even if current request has ended.
        // $user = new User();
        // $user->name = implode('; ',$a);
        // $user->save();
        
    });

    $a[] = 'outer1';
    

    // go() can use laravel service  with closure
    $log = app('log');
    go(function () use (&$a, $log) {
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



## Similar projects that mix swoole and laravel

The main distinguishing feature of LaravalFly is refactoring Laravel, like what Django 3.0 does.

### 1. [laravel-swoole](https://github.com/swooletw/laravel-swoole) 

It is alse a safe sollution. It has supported Lumen and websocket. Its doc is great and also useful for LaravelFly.   

The main difference is that in laravel-swoole user's code will be processed by a new `app` cloned from SwooleTW\Http\Server\Application::$application and laravel-swoole updates related container bindings to the new app. However in LaravelFly, the sandbox is not a new app, but an item in the $corDict of the unique application container.   
In LaravelFly, most other objects such as `app`, `event`.... always keep one object in a worker process, `clone` is not used at all by default. LaravelFly makes most of laravel objects keep safe on their own. It's about high cohesion & low coupling and the granularity is at the level of app container or services/objects. For users of laravel-swoole, it's a big challenge to handle the relations of multiple packages and objects which to be booted before any requests. Read [Stale Reference](https://github.com/scil/LaravelFly/wiki/clone-and-Stale-Reference). 

 .  | technique | work to maintaining relations of cloned objects to avoid Stale Reference 
------------ |------------ | ------------ 
laravel-swoole  | clone app contaniner and objects to make them safe | more work (as app,event...are cloned)
LaravelFly Mode Map |  refactor most official objects to make them safe on their own | few work ( nothing is cloned by default)

In LaravelFly, another benefit of non-cloned objects is allowing some improvements, such as event listeners cache, route middlewares cache.

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
- [x] db connection pool and redis connection pool. In `fly()` or `fly2()`, connections to be used would be fetched from pool, not inherit the same connections from request coroutine. code: `$this->connections[$childId] = [];` in ConnectionsTrait.php
- [x] swoole redis driver
- [ ] swoole redis driver: how to use `errMsg` `errCode`
- [ ] use [swlib/swpdo](https://github.com/swlib/swpdo) to replace SwoolePDO?
- [ ] Cache for HasRelationships. disable and experimental, not ready
- [x] Cache for RouteDependencyResolverTrait 
- [ ] Converting between swoole request/response and Laravel Request/Response
- [ ] safe: auth, remove some props?
- [ ] use: https://github.com/louislivi/smproxy
- [ ] use: https://github.com/kcloze/swoole-jobs or https://github.com/osgochina/Donkey

## Other Todo

- [x] add events
- [x] watch code changes and hot reload
- [x] supply server info. default url is: /laravel-fly/info
- [x] function fly()
- [x] job executed in task process. Related: vendor\scil\laravel-fly-files-local\src\Foundation\Bus\
- [x] event listeners executed in task process. Related: LaravelFly\Map\IlluminateBase\Dispatcher and vendor\scil\laravel-fly-files\src\Foundation\Support\Providers\EventServiceProvider.php
- [ ] use $this->output instead of echo() in Common.php
- [ ] bootstrap all service providers in task process
- [ ] try ocramius/generated-hydrator for laravel-fly/info when its version 3 is ready (it will require nikic/php-parser v4 which is needed by others)  // or Zend\Hydrator\Reflection?
- [ ] add tests about auth SessionGuard: Illuminate/Auth/SessionGuard.php with uses Request::createFromGlobals
- [ ] add tests about uploaded file, related symfony/http-foundation files: File/UploadedFile.php  and FileBag.php(fixPhpFilesArray)
- [ ] websocket
- [ ] send file
- [ ] travis, static analyze like phan, phpstan or https://github.com/exakat/php-static-analysis-tools
- [ ] cache fly

