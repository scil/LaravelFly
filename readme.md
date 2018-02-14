LaravelFly runs Laravel much faster, and make Tinker to be used online(when Laravel is working).

Thanks to [Swoole](https://github.com/swoole/swoole-src) and [PsySh](https://github.com/bobthecow/psysh)

## Doc

[Install & Config](doc/install.md)

[Run, Stop, Restop, Load & Debug](doc/server.md)

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

* A visit to http://zhenc.test/green relates to 5 Models and 5 db query.
* env:   
ubuntu 16.04 on virtualbox ( 2 CPU: i5-2450M 2.50GHz ; Memory: 1G  )  
php7.1 + opcache + 5 workers for both fpm and laravelfly ( phpfpm : pm=static  pm.max_children=5)
* Test date : 2018/02
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

The response may be:
```
Hello, Tinker!

Hello, PsySh!

Hello, World!
```

Visit 'http://server.name/hi' from localhost, enter tinker shell and do like this: 

[![tinker()](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD.png)](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD?t=3)

The tinker() demo to read/write vars, use Log::info. 

### You can try these commands:
```php
// visit private members
sudo app()->booted

// use Model or Controller without writing namespace, thanks to ClassAliasAutoloader
// and the instance is printed beautifully, thanks to casters provided by laravel
$user = User::first()

// which class aliases are defined
sudo app('tinker')->loader->classes

// like dir() in Python
ls -la $user

// read doc
doc $user->save

// check code
show $user->query

// check server pid and pidfile
LaravelFly::getServer()

// run shell commands
`pwd && ls `

// magic var
$__file

```

`eval(tinker())` is a `eval(\Psy\sh())` with extra support for Laravel. It can be used independently without LaravelFly server, but LaravelFly applies the opportunity to use shell online.

There may be a problem with tabcompletion. see [tabcompletion only works "the second time](https://github.com/bobthecow/psysh/issues/435)


## Usability 

It can be installed on your existing projects without affecting nginx/apache server, that's to say, you can run LaravelFly server and nginx/apache server simultaneously to run the same laravel project.

There is a nginx conf [swoole_fallback_to_phpfpm.conf](config/swoole_fallback_to_phpfpm.conf) which let you use LaravelFlyServer as the primary server, and the phpfpm as a backup tool which will be passed requests when the LaravelFlyServer is unavailable. .

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

- [ ] add tests
- [ ] Laravel5.5, like package auto-detection
- [ ] send file
- [ ] try to add Providers with concurrent services, like mysql , redis;  add cache to Log

## Similar projects that use swoole and laravel

* [laravoole](https://github.com/garveen/laravoole) : wonderful with many merits which LaravelFly will study. Caution: laravoole loads the app before any request ([onWorkerStart->parent::prepareKernel](https://github.com/garveen/laravoole/blob/master/src/Wrapper/Swoole.php)),  but it ignores data pollution, so please do not use any service which may change during a request, do not write any code that may change Laravel app or app('event') during a request, such as event registering.
