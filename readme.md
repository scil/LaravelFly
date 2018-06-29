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

## Speed Test

### A simple ab test 

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

* [laravel-swoole](https://github.com/swooletw/laravel-swoole): It is alse a safe sollution. The main difference is that it uses clone almost everything to achiev safety, however LaravelFly uses only twice in Mode Map. Why? Because clone create new objects. Give an object X1, and another object Y holding a ref to X1, in a new request X1 is cloned to produce a new object X2, but object Y is still holding X1, not X2.

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: laravoole loads the app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as registering event .

## Todo Abut Safe: Mode Simple

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
Application   | √  |  needBackUpAppAttributes in LaravelFly\Simple\Application | √| | -
Kernel   | 🔧  |     | 🔧 | Methods pushMiddleware or prependMiddleware? No worry about middlewares are added multiple times, because there's a check: ` if (array_search($middleware, $this->middleware) === false)` | LARAVELFLY_SERVICES['kernel'] and config('laravelfly.BaseServices')[\Illuminate\Contracts\Http\Kernel::class]
Base Services: events | √  |     | √ | | config('laravelfly.BaseServices')['events']
Base Services: router | √  |     | | | config('laravelfly.BaseServices')['router']
Base Services: router.routes | 🔧 |     |  √ | props are associate arrays| LARAVELFLY_SERVICES['routes'] and config('laravelfly.BaseServices')['router.obj.routes']
Base Services: url(UrlGenerator) | 🔧 |    | | | config('laravelfly.BaseServices')['url']
Facade | √  |  Facade::clearResolvedInstances   | NA | | 
Laravel config | 🔧  |  FLY. And setBackupedConfig in LaravelFly\Simple\Application. | 🔧 | Methods push and prepend | LARAVELFLY_SERVICES['config']
PHP Config | ×  | | NA |  | 

🔧: configurable

Php Config not planed to support:    
1. It's useless 
2. It's hard to achive as it's related with php internal function ini_set.  

## Todo Abut Safe: Mode Map

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
Application   | √  |     | | | -
Kernel   | 🔧  |     | 🔧 | Illuminate\Foundation\Http\Kernel::pushMiddleware or prependMiddleware? No worry about middlewares are added multiple times, because there's a check: ` if (array_search($middleware, $this->middleware) === false)` | LARAVELFLY_SERVICES['kernel'], config('laravelfly.BaseServices')[\Illuminate\Contracts\Http\Kernel::class]
Illuminate\Support\ServiceProvider  | 🖏  |     | √ | 'publishes' and 'publishGroups' are associate arrays and used only in artisan commands.| 
Base Services: events | √  |  Dict   | √| Dict | 
Base Services: router | √  |     | | | 
Base Services: router.routes | 🔧 |  clone   |  √ | props are associate arrays| LARAVELFLY_SERVICES['routes'] 
Base Services: url(UrlGenerator) | 🖏  |  clone🤝 ,its routes and request would update auto (registerUrlGenerator) and also routeGenerator when setRequest. But four props 'sessionResolver','keyResolver', 'formatHostUsing','formatPathUsing' are not cloned, as closure | √ | | 
Facade | √  |  Dict   | | | 
Laravel config | 🔧  |  Dict | 🔧 | Methods push and prepend. | LARAVELFLY_SERVICES['config']
PHP Config | ×  | | √ |  | 
routes |  🔧 |  Dict   | √ | most cases, no problems, because props in RouteCollection are associate arrays.|  LARAVELFLY_SERVICES['routes']

🖏: works well in most cases, except basic config different in different requests. for example, UrlGenerator::$formatHostUsing is a callback/closure and keep same in most projects.But if your project has different formatHostUsing, plus hack work is needed.
🔧🖏: configurable, and works well in most cases after configration.



## None-Base Services
Then can be booted on worker or not.If you boot some of them before any requests, this table is useful.

item   | Data Pollution  |  note | Memory Leak| note| config
------------ | ------------ | ------------- | ------------- | ------------- | ------------- 
view.finder | √  |  Dict   | √ | addNamespace offen called by loadViewsFrom of ServiceProviders such as PaginationServiceProvider  and NotificationServiceProvider.|  
cookie(CookieJar) | 🔧🖏  |  Dict   | √ |  Dict version considers prop 'queued',but path, domain, secure and sameSite  are not rewriten.| config('laravel.providers_on_worker')[LaravelFly\Map\Illuminate\Cookie\CookieServiceProvider::class ] 
PaginationServiceProvider  | 🖏  |     | √ | the static props like currentPathResolver, ... in Illuminate\Pagination\AbstractPaginator keep same.  | 



- [ ] AuthServiceProvider 


support no planned
- [ ] .No plan to make its members 
- [ ] Laravel Macros. In Mode Map, macros are not supported to avoid data pollution, because in most situations macros are always same.
- [ ] Php Config. It's not supported in the near future. 


## Todo About Improvement

- [x] Config cache. laravelfly_ps_map.php or laravelfly_ps_simple.php located bootstrap/cache
- [x] Log cache. Server config 'log_cache'.
- [x] Cache for view compiled path. LARAVELFLY_SERVICES['view.finder'] or  App config 'view_compile_1'
- [x] Watching maintenance mode using swoole_event_add. No need to check file storage/framework/down in every request.
- [x] Pre-include. Server configs 'pre_include' and 'pre_files'.
- [x] Server config 'early_laravel'
- [x] Mysql coroutine
- [ ] Mysql connection pool
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

