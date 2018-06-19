## Config

1. Edit app config file `<project_root_dir>/config/laravelfly.php`. (produced by `php artisan vendor:publish --tag=fly-app`)  
Note: items prefixed with "/** depends " deverve your consideration.

2. Publish server config file  
`php artisan vendor:publish --tag=fly-server`  .  

3. Edit server config file  
`<project_root_dir>/fly.conf.php`.


## Optional Config

* `composer require --dev "eaglewu/swoole-ide-helper:dev-master"` , which is useful in IDE development.

* Config and restart nginx: swoole http server lacks some http functions, so it's better to use swoole with other http servers like nginx. There is a nginx site conf example at `vendor/scil/laravel-fly/config/nginx+swoole.conf`.


* if you want to use mysql persistent, add following to config/database.php
```
'options' => [
    PDO::ATTR_PERSISTENT => true,
],
```
* In Mode Map, MySql coroutine can be used. Add `'coroutine' => true,` to config/database. This feature is still under dev.And you must disable xdebug or similar library.
```
'mysql' => [
    'driver' => 'mysql',
    'coroutine' => true,
],
```


## Two optional steps to allow you use same code for LaravelFly and PHP-FPM

1. Edit `<project_root_dir>/app/Http/Kernel.php`, change `class Kernel extends HttpKernel ` to
```
if (defined('LARAVELFLY_MODE')) {
    if (LARAVELFLY_MODE == 'Map') {
        class WhichKernel extends \LaravelFly\Map\Kernel { }
    }elseif (LARAVELFLY_MODE == 'Simple') {
        class WhichKernel extends \LaravelFly\Simple\Kernel { }
    } elseif (LARAVELFLY_MODE == 'FpmLike') {
        class WhichKernel extends HttpKernel{}
    } 
} else {
    class WhichKernel extends HttpKernel { }
}

class Kernel extends WhichKernel
```


2. If you use tinker(), put this line at the top of `public/index.php` :  
` function tinker(){ return '';} `  
This line avoids error `Call to undefined function tinker()`  when you use php-fpm with tinker() left in your code.


## Config examples for Third Party Serivce
* [Debugbar](package_config_examples/Debugbar.md)
* [AsgardCms](package_config_examples/AsgardCms.md)


