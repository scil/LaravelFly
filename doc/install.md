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
    if (LARAVELFLY_MODE == 'Dict') {
        class WhichKernel extends \LaravelFly\Dict\Kernel { }
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
* In Mode Dict,coroutine can be used for mysql. Please compile swoole with  --enable-coroutine, then disable xdebug, xhprof, or blackfire. Yes, currently, swoole not compatible with them. Third, add `'coroutine' => true,` to config/database. This feature is still under dev.And you must disable xdebug or similar library.
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


