LaravelFly speeds up our existing Laravel projects without data pollution and memory leak, and make Tinker to be used online (use tinker while Laravel is responding requests from browsers).

Thanks to [Laravel](http://laravel.com/), [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## Version Compatibility

- Laravel 5.6.*
- swoole >4.0

## Features

- Same codes can run on PHP-FPM or LaravelFly
- To be absolutely safe, put your code under control.
- A laravel service that made before any requests can be configurable to serve in multiple requests (only one instance of the service), or to be cloned in each request (one instance in one request).LaravelFly named them COROUTINE-FRIENDLY SERVICE and CLONE SERVICE.
- Extra speed improvements such as log cache, view path cache.

## Requirements or Code Tips
In most cases, our projects running on a base of consistent and stable configuration. Using LaravelFly, in each request, these items of env should always be same:

1. php configuration should keep same in any requests.
2. In LaravelFly Mode Map, some objects are created before any requests by default, but not fully refactored because that is useless most time. Have a look:
    1. [Laravel Macros](https://tighten.co/blog/the-magic-of-laravel-macros/) with same name should always be same.
    2. static props should be always same.
        1. Pagination: **currentPathResolver,currentPageResolver,viewFactoryResolver,defaultView,defaultSimpleView** .

## Quick Start

1.`pecl install swoole`   
Make sure `extension=swoole.so` in config file for php cli.   
Suggest: `pecl install inotify`   

2.`composer require "scil/laravel-fly":"dev-master"`

3.`php vendor/scil/laravel-fly/bin/fly start`   
If you enable `eval(tinker())` and see an error about mkdir, please start LaravelFly using sudo.

Now, your project is flying and listening to port 9501. Enjoy yourself.

## Doc

[Configuration](https://github.com/scil/LaravelFly/wiki/Configuration)

[Commands: Start, Reload & Debug](doc/server.md)

[Coding Tips](doc/coding.md)

[Events about LaravelFly](doc/events.md)

[Using tinker when Laravel Working](doc/tinker.md)

[For Dev](doc/dev.md)

## A simple ab test 

 `ab -k -n 1000 -c 10 http://zc.test/green `

.   | fpm |  Fly Mode Simple | Fly Mode Map(using coroutine)
------------ | ------------ | ------------- | ------------- 
Requests per second   | 3 |  5  | 34
Time taken ≈ | 325 | 195  | 30
  50%  | 2538|   167  | 126
  80%  |   3213|  383   | 187
  99%   | 38584| 33720  | 3903

<details>
<summary>Test Env</summary>
<div>


* A visit to http://zc.test/green relates to 5 Models and 5 db query.
* env:   
ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 1G  )  
php7.1 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)
* Test date : 2018/02

</div>
</details>

## LaravelFly Usability 

It can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run the same laravel project.

The nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) allow you use LaravelFlyServer as the primary server, and the phpfpm as a backup server which will be passed requests when the LaravelFlyServer is unavailable. .

Another nginx conf [use_swoole_or_fpm_depending_on_clients](config/use_swoole_or_fpm_depending_on_clients.conf) allows us use query string `?useserver=<swoole|fpm|...` to select the server between swoole or fpm. That's wonderful for test, such as to use eval(tinker()) as a online debugger for your fpm-supported projects.

## Similar projects that mix swoole and laravel

### 1. [laravel-swoole](https://github.com/swooletw/laravel-swoole) 

It is alse a safe sollution. It is light.It has supported Lumen and websocket. Its doc is great and also useful for LaravelFly.   

The first difference is that laravel-swoole's configuration is based on service,like log, view while LaravelFly is based on service providers like LogServiceProvider, ViewServiceProvider.(In Mode Simple, providers are registered before any requests, in Mode Map some providers are registered and booted before any requests.) 

The main difference is that in laravel-swoole requests will be processed by a new `app` cloned from the original app container and laravel-swoole updates related container bindings to the new app. However in LaravelFly, the sandbox is not a new app, but an item in the $corDict of the unique application container. In LaravelFly, most other objects such as `app`, `event`.... always keep one object in a worker process, `clone` is used only to create `url` (and `routes` when LARAVELFLY_SERVICES['routes'] ===false ) in Mode Map. LaravelFly makes most of laravel objects keep safe on its own. It's about high cohesion & low coupling and the granularity is at the level of app container or services/objects. For users of laravel-swoole, it's a big challenge to handle the relations of multiple packages and objects which to be booted before any requests. Read "Stale Reference" in [Mode Map Safety Checklist](https://github.com/scil/LaravelFly/wiki/Mode-Map-Safety-Checklist)`. 

.   | technique | every service is in control |  every service provider is in control | work to maintaining relations of cloned objects to avoid Stale Reference
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
laravel-swoole  | clone app contaniner and objects to make them safe |  no | no | more work (app,event...are cloned)
LaravelFly Mode Map  | refactor most official objects to make them safe on their own |  yes  | yes  | less work (only url and routes cloned)
LaravelFly Mode Simple  | service providers reg on work and boot in requests | yes | yes | less work 


### 2. [laravoole](https://github.com/garveen/laravoole) 
wonderful with many merits which LaravelFly will study. Caution: laravoole loads the app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as registering event .

## Todo About Improvement

- [x] Config cache. laravelfly_ps_map.php or laravelfly_ps_simple.php located bootstrap/cache
- [x] Log cache. Server config 'log_cache'.
- [x] Cache for view compiled path. LARAVELFLY_SERVICES['view.finder'] or  App config 'view_compile_1'
- [x] Watching maintenance mode using swoole_event_add. No need to check file storage/framework/down in every request.
- [x] Pre-include. Server configs 'pre_include' and 'pre_files'.
- [x] Server config 'early_laravel'
- [x] Mysql coroutine
- [ ] Mysql connection pool
- [ ] event: wildcardsCache? keep in memory，no clean?
- [ ] Converting between swoole request/response and Laravel Request/Response
- [ ] safe: router, remove middleware?
- [ ] safe: auth, remove some props?
- [ ] check memory usage in Mode Map

## Other Todo

- [x] add events
- [x] watch code changes and hot reload
- [ ] add tests about auth SessionGuard: Illuminate/Auth/SessionGuard.php with uses Request::createFromGlobals
- [ ] add tests about uploaded file, related symfony/http-foundation files: File/UploadedFile.php  and FileBag.php(fixPhpFilesArray)
- [ ] websocket
- [ ] send file
- [ ] travis, static analyze like phan, phpstan or https://github.com/exakat/php-static-analysis-tools
- [ ] decrease worker ready time
- [ ] cache fly

