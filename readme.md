LaravelFly runs Laravel much faster, and make Tinker to be used online(use tinker while Laravel is responding requests from browsers).

Thanks to [Laravel](http://laravel.com/), [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## Quick Start

1.`pecl install swoole`   
Make sure `extension=swoole.so` in config file for php cli.   
LaravelFly Mode Map requires swoole 4.0.  
Suggest: `pecl install inotify`   

2.`composer require "scil/laravel-fly":"dev-master"`

3.`php artisan vendor:publish --tag=fly-app`   
This is publishing an app config file 

4.`php vendor/scil/laravel-fly/bin/fly start`   
If you enable `eval(tinker())` and see an error about mkdir, please start LaravelFly using sudo.

Now, your project is flying and listening to port 9501. Enjoy yourself.

By default, every time LaravelFly starts, it makes a config cache file laravelfly_ps_map.php or laravelfly_ps_simple.php located bootstrap/cache, so if config file `config/laravelfly.php` changes you can change config 'laravelfly.config_cache_always' to false in dev env , or run `php artisan config:clear` before starting LaravelFly in production env:
```
alias ff='php artisan config:clear && php vendor/scil/laravel-fly/bin/fly start'

ff
```


## Doc

[Configuration](doc/config.md)

[Commands: Start, Reload & Debug](doc/server.md)

[Coding Tips](doc/coding.md)

[Events about LaravelFly](doc/events.md)

[Using tinker when Laravel Working](doc/tinker.md)

[LaravelFly Execution Flow](doc/flow.md)

[For Dev](doc/dev.md)

## A simple ab test 

 `ab -k -n 1000 -c 10 http://zc.test/green `

.   | fpm |  Fly Mode Simple | Fly Mode Map
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

* [laravel-swoole](https://github.com/swooletw/laravel-swoole): It is alse a safe sollution. It is light.It has supported Lumen and websocket. Its doc is great and also useful for LaravelFly. The main difference is that all the requests will be processed by a new `sandbox app` cloned from the original app container and laravel-swoole updates related container bindings to sandbox. However in LaravelFly, `clone` is used only twice to create `url` and `routes` in Mode Map, and other objects such as `app`, `event`.... always keep one object to handle requests in a worker process. LaravelFly makes most of laravel objects keep safe on its own. It's about high cohesion & low coupling. See `Stale Reference` part of this readme. 

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: laravoole loads the app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as registering event .

## Mode Simple Safety Checklist

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
Application   | √  |  | √| | -
Kernel   | 🔧  |     | 🔧 | Methods pushMiddleware or prependMiddleware? No worry about middlewares are added multiple times, because there's a check: ` if (array_search($middleware, $this->middleware) === false)` | LARAVELFLY_SERVICES['kernel'] and config('laravelfly.BaseServices')[\Illuminate\Contracts\Http\Kernel::class]
events | √  |     | √ | | config('laravelfly.BaseServices')['events']
router | 🔧🖏 |  dif __macros__ not supported| | | config('laravelfly.BaseServices')['router']
router.routes | 🔧 |     |  √ | props are associate arrays| LARAVELFLY_SERVICES['routes'] and config('laravelfly.BaseServices')['router.obj.routes']
url(UrlGenerator) |  🔧🖏 |  dif __macros__ not supported | | | config('laravelfly.BaseServices')['url']
redirect(Redirector) | 🖏 |  dif __macros__ not supported | | | config('laravelfly.BaseServices')['url']
Facade | √  |  Facade::clearResolvedInstances   | NA | | 
config | 🔧  |  FLY | 🔧 | Methods push and prepend | LARAVELFLY_SERVICES['config']
PHP Config | 🖏  | should not changed in any requests | NA |  | 


- 🔧: configurable
- 🖏: works well in most cases, except basic config different in different requests. for example, UrlGenerator::$formatHostUsing is a callback/closure and keep same in most projects.But if your project has different formatHostUsing, plus hack work is needed.
- 🔧🖏: configurable, and works well in most cases after configration.
- NA: not applicable


## Mode Map Safety Checklist on Base Items

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
Application   | √  |   | | | -
Kernel   | 🔧  |     | 🔧 | Kernel::pushMiddleware or prependMiddleware? No worry, because there's a check: ` if (array_search($middleware, $this->middleware) === false)` | LARAVELFLY_SERVICES['kernel'], config('laravelfly.BaseServices')[\Illuminate\Contracts\Http\Kernel::class]
ServiceProvider  | 🖏  | __'publishes', 'publishGroups'__ are used mainly in php artisan   | √ | props are associate arrays | 
events | √  |  Dict   | √| Dict | 
router | √  |     | | | 
routes |  🔧 |  clone👀  | √ | props are associate arrays.|  LARAVELFLY_SERVICES['routes']
url(UrlGenerator) | 🖏  |  clone👀 [1],but four closure props __'sessionResolver','keyResolver', 'formatHostUsing','formatPathUsing'__ are not cloned | √ | | 
Facade | √  |  Dict   | | | 
config | 🔧  |  Dict | 🔧 | Methods set/push/prepend. | LARAVELFLY_SERVICES['config']
PHP Config | 🖏  | should not changed in any requests | NA |  | 

[1]: Props routes and request of url would update (registerUrlGenerator) and also routeGenerator when setRequest.

###  clone👀 and Stale Reference
`clone` creates new objects. Give an object X1, and another object Y holding a ref to X1, in a new request X1 is cloned to produce a new object X2, but object Y is still holding X1, not X2. So developers and users should pay some attention to this kind of relations.

The second problem is that by default `clone` does not clone props of type closure or object. 

Objects url and routes have ref in Laravel offical objects , but rarely have ref in your code. So `clone` is used. While other objects, such as event, are used widely in your code, so `clone` is not used, trait `Dict` is used.

clone👀 means LaravelFly has handled the Stale Reference problems in Laravel Official objects.

## Mode Map Safety Checklist on None-Base Items

Objects here can boot on worker or not.If you boot some of them before any requests, this table is useful.

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
view.finder | √  |  Dict   | √ | addNamespace offen called by loadViewsFrom of ServiceProviders such as PaginationServiceProvider  and NotificationServiceProvider.|  
cookie(CookieJar) | 🔧🖏  |  Dict   | √ |  prop 'queued' can dif,but path, domain, secure and sameSite should keep same.| config('laravel.providers_on_worker')[LaravelFly\Map\Illuminate\Cookie\CookieServiceProvider::class ] 
Pagination | 🖏  |     | √ | the static props like currentPathResolver, ... in Illuminate\Pagination\AbstractPaginator should keep same.  | 
auth  |  |     |  | | 


support no planned
- [ ] Laravel Macros. In Mode Map, macros are not supported to avoid data pollution, because in most situations macros are always same.


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

