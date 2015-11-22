
## How

[Swoole](https://github.com/swoole/swoole-src) is an event-based & concurrent tool , written in C, for PHP. The memory allocated in Swoole worker will not be free'd after a request, that can improve preformance a lot.A swoole worker is like a php-fpm worker, every swoole worker is an independent process. When a fatal php error occurs, or a worker is killed by someone, or 'max_request' is handled, the worker would die and a new worker will be created.

LaravelFly loads resources as more as possible before any request. For example , \Illuminate\Foundation\Application instantiates when  a swoole worker start , before any request.

The problem is that, objects which created before request may be changed during a request, and the changes maybe not right for subsequent requests.For example, `app('view')` has a protected property "shared", which is not appropriate to share this property across different requests.

So the key is to backup some objects before any request, and restore them after each request handling has finished.`\LaravelFly\Application` extends `\Illuminate\Foundation\Application` , use method "backUpOnWorker" to backup, and use method "restoreAfterRequest" to restore.

## Normal Mode and Greedy Mode

First, let's take a look at `Illuminate\Foundation\Http\Kernel::$bootstrappers`:
```
    protected $bootstrappers = [
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'Illuminate\Foundation\Bootstrap\LoadConfiguration',
        'Illuminate\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',
        'Illuminate\Foundation\Bootstrap\BootProviders',
    ];
```
The "$bootstrappers" is what Laravel do before handling a request, LaravelFly execute them before any request, except the last two items "RegisterProviders" and "BootProviders"

In Normal Mode, "RegisterProviders" is placed on "WorkerStart", "BootProviders" is placed on "request". That means, all providers are registered before any requests and booted after each request.The only exception is, providers in "config('laravelfly.providers_in_request')" are registered and booted after each request.

In Greedy Mode, providers in "config('laravelfly.providers_in_worker')" are registered and booted before any request. Other providers follow Normal Mode rule. 

And In Greedy Mode, you can define which singleton services to made before any request in "config('laravelfly.services_to_make_in_worker')".If necessary, you should define which properties need to backup and restore. 

You can choose Mode in <project_root_dir>/laravelfly.server.php after you publish config files..

## Install

1. Install php extension swoole
2. Open terminal and execute "composer require barryvdh/laravel-ide-helper"
3. If your user would upload files to your server, edit composer.json
    1. Add "vendor/bin/hack-laravel-for-laravelfly" to 'post-install-cmd' and 'post-update-cmd'
    2. If necessary, manually execute "vendor/bin/hack-laravel-for-laravelfly" .

## Config

1. Open terminal and execute "vendor/bin/publish-laravelfly-config-files"  .you can add argument "force" to overwrite old config files."vendor/bin/publish-laravelfly-config-files force"
2. Edit <project_root_dir>/laravelfly.server.php.
3. Edit <project_root_dir>/config/laravelfly.php. Note: about 'backup and restore', any items prefixed with "/* depends */" need your consideration.
4. Optional: put LaravelFly files to <project_root_dir>/config/compile.php.
You can get file list by executing `find vendor/scil/laravel-fly/src/LaravelFly -name "*.php" | sed -n "s/.*/realpath(__DIR__.'\/..\/&'),/p"` at project root dir.
5. Edit <project_root_dir>/app/Http/Kernel.php, change `class Kernel extends HttpKernel {` to
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
if you want to use mysql persistent, add following to config/database.php ( do not worry about "server has gone away", laravel would reconnect it auto)
```
        'options'   => [
            PDO::ATTR_PERSISTENT => true,
        ],
```



## Run

1. Execute "vendor/bin/start-laravelfly-server <absolute_path_of_server_config_file>"
   Argument <absolute_path_of_server_config_file> is optional, default is <project_root_dir>/laravelfly.server.php.
2. Config and restart nginx: swoole http server lacks some http functions, so it's better to use swoole with other http servers like nginx. There is a nginx site conf example at "vendor/scil/laravel-fly/config/nginx+swoole.conf".


## Stop

Two ways:
* send SIGTERM to swoole server main process: " kill -15 `ps a | grep start-laravelfly-server| awk 'NR==1 {print $1}'`"
* in php , `$server->shutdown();` 


## Restart All Workers Gracefully: swoole server reloading

Swoole server has a main process, a manager process and one or more worker processes.If you set `'worker_num' => 4`, there are 6 processes.The first the main process, the second is the manager process, and the last four are all worker processes.

Swoole server reloading has no matter with the main process or the manager process. Swoole server reloading is killing worker processes gracefully and start new.

Gracefully is that: worker willl finish its work before die.

Two ways to reload
* in php , you can make your own swoole server by extending 'LaravelFlyServer', and use `$server->reload();` under some conditions like some files changed.
* open terminal and execute "kill -USR1 `ps a | grep start-laravelfly-server| awk 'NR==2 {print $1}'`"

Details:
1. Send USR1 to swoole manager process
2. swoole manager process send TERM to all worker processes
3. Every worker first finish it's work, then call OnWorkerStop callback, then kill itself.
4. manager process creates new worker processes.

## Hot Reload On Code Change

By using swoole server reloading, it's possible to hot reload on code change, because any files required or included in 'WorkerStart' callback will be requied or included again when a new worker starts.

Note, files required or included before 'WorkerStart' will keep in memory, even swoole server reloads.

So it's better to include/require files which change rarely before 'WorkerStart' to save memory, include/require files which change often in 'WorkerStart' callback to hot reload.

You could moniter some files and execute "kill -USR1 `ps a | grep start-laravelfly-server| awk 'NR==2 {print $1}'`" to hot reload , just make sure there files are required/included in 'WorkerStart' callback.

If you use APC/OpCache, you could use one of these measures
* edit php.ini and make APC/OpCache to hot reload opcode
* edit swoole server code:
```
  function onWorkerStop($serv, $worker_id) {
       opcache_reset(); // opcache reset function, use similar function if you use APC
  }
```

## Memory

Memory usage may grow slowly. You can set 'max_request' to a small number.

A simple way to detect memory usage growth is use Apache ab.exe and linux command "free"
