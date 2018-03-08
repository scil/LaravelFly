LaravelFly runs Laravel much faster, and make Tinker to be used online(use tinker while Laravel is responding requests from browsers).

Thanks to [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## Quick Start

1.`pecl install swoole`   
Make sure `extension=swoole.so` in config file for php cli.

2.`composer require "scil/laravel-fly":"dev-master"`

3.`php vendor/scil/laravel-fly/bin/fly start`   
If you enable `eval(tinker())` and see an error about mkdir, please start LaravelFly using sudo.

Now, your project is flying and listening to port 9501. Enjoy yourself.

## Doc

[Config](doc/config.md)

[Start, Reload & Debug](doc/server.md)

[How LaravelFly Works](doc/design.md)

## Speed Test

### A simple ab test 

 `ab -k -n 1000 -c 10 http://zc.test/green `

.   | fpm |  Fly Mode Simple | Fly Mode Dict
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

## Tinker online: use tinker when laravel is working

### used in router

```php
Route::get('hi', function () {
    $friends = 'Tinker';

    if (starts_with(Request::ip(), ['192.168', '127'])) {
        eval(tinker());
    }

    return view('fly', compact('friends'));
});
```

### used in view file

```blade.php
@php(eval(tinker()))

@foreach($friends as $one)

    <p>Hello, {!! $one !!}!</p>

@endforeach
```

<details>
<summary>The response can be changed to anything by you.</summary>
<div>

```
Hello, Tinker!

Hello, PsySh!

Hello, World!
```


</div>
</details>



### tinker abilities

visit private members, read/write vars, use laravel services and so on.

<details>
<summary>tinker use examples.</summary>
<div>


```php
// visit private members
sudo app()->booted
sudo $url= app()->corDict[1]['instances']['url']

// use Model or Controller without writing namespace, thanks to ClassAliasAutoloader
// and the instance is printed beautifully, thanks to casters provided by laravel
$user = User::first()

// like dir() in Python
ls -la $user

// read doc
doc $user->save

// check code
show $user->query

// use xdebug
xdebug_debug_zval('user')
xdebug_debug_zval('url->routes')
xdebug_call_class()

// magic var
$__file

// check server pid and pidfile
LaravelFly::getServer()

// which class aliases are defined in tinker
sudo app('tinker')->loader->classes

// run shell commands
`pwd && ls `

```

</div>
</details>


### tinker demo

[![tinker()](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD.png)](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD?t=3)


### Tinker Tips

`eval(tinker())` is a `eval(\Psy\sh())` with extra support for Laravel. It can be used independently without LaravelFly server, but LaravelFly applies the opportunity to use shell online.

There may be a problem with tabcompletion. see [tabcompletion only works "the second time](https://github.com/bobthecow/psysh/issues/435)

If you see an error about mkdir, please start LaravelFly using sudo.

## Usability 

It can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run the same laravel project.

The nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) allow you use LaravelFlyServer as the primary server, and the phpfpm as a backup server which will be passed requests when the LaravelFlyServer is unavailable. .

Another nginx conf [use_swoole_or_fpm_depending_on_clients](config/use_swoole_or_fpm_depending_on_clients.conf) allows us use query string `?useserver=<swoole|fpm|...` to select the server between swoole or fpm. That's wonderful for test, such as to use eval(tinker()) as a online debugger for your fpm-supported projects.

## Tips for use

### php functions not fit Swoole/LaravelFly

name | replacement
------------ | ------------ 
header | Laravel api: $response->header
setcookie | Laravel api: $response->cookie

### Mode Simple vs Mode Dict

features  |  Mode Simple | Mode Dict 
------------ | ------------ | ------------- 
global vars like $_GET, $_POST | yes  | no
coroutine| no  | yes (conditional*)

### Todo

- [x] Laravel5.5 package auto-detection
- [x] mysql coroutine
- [ ] mysql connection pool
- [ ] handle php config and laravel config like Zend in Mode Simple?
- [ ] handle php config and laravel config in Mode Dict?
- [ ] websocket
- [ ] add more tests
- [ ] send file
- [ ] log fly: improve log on swoole
- [ ] cache fly

## Similar projects that use swoole and laravel

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: laravoole loads the app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as event registering.
