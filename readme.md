Tinker can be used online and laravel can be much faster by LaravelFly. Thanks to [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## Doc

[Install & Config](doc/install.md)

[Run, Stop, Restop, Load & Debug](doc/server.md)

[How LaravelFly Works](doc/design.md)

## Tinker online: use tinker when laravel is working

```php
Route::get('hi', function () {
    $friends = 'Tinker';

    if (starts_with(Request::ip(), ['192.168', '127'])) {
        eval(tinker());
    }

    return view('fly', compact('friends'));
});
```

blade file for view('fly') 

```blade.php
@php(eval(tinker()))

@foreach($friends as $one)

    <p>Hello, {!! $one !!}!</p>

@endforeach
```

The response may be:
```
Hello, Tinker!

Hello, PsySh!

Hello, World!
```

Visit 'http://server.name/hi' from localhost, enter tinker shell and do like this: 

[![tinker()](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD.png)](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD?t=3)

The tinker() demo to read/write vars, use Log::info and so on.

## Speed Test

### A simple ab test 

env:   
ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 1G  )  
php7.1 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)

`ab -k -n 1000 -c 10 http://zhenc.test/green `

item   | fpm |  Fly Simple | Fly Coroutine
------------ | ------------ | ------------- | ------------- 
Time taken for tests | 325 | 193.44  | 29.17
Requests per second   | 3.08|  5.17  | 34.28
  50%  | 2538|   167  | 126
  80%  |   3213|  383   | 187
  99%   | 38584| 33720  | 3903

* Test date : 2018/02
* Visited url relates to 5 Modes and 5 db query.

## Usability 

It's a composer package based on swoole and can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run same laravel project.

There is a nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) which let you use LaravelFlyServer as the primary server, and the phpfpm as a backup tool which will be passed requests when the LaravelFlyServer is unavailable. .

## Tips for use

### Mode Simple vs Mode Coroutine

features  |  Mode Simple | Mode Coroutine 
------------ | ------------ | ------------- 
global vars like $_GET, $_POST | yes  | no
coroutine| no  | yes (conditional*)

### php functions not fit Swoole/LaravelFly

name | replacement
------------ | ------------ 
header | Laravel api: $response->header
setcookie | Laravel api: $response->cookie

### Todo

- [ ] add tests
- [ ] Laravel5.5, like package auto-detection
- [ ] send file
- [ ] try to add Providers with concurrent services, like mysql , redis;  add cache to Log

## Similar projects that use swoole and laravel

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: like LaravelFly, laravoole loads app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as event registering.
