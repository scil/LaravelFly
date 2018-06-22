# Tinker online 

## tinker demo

[![tinker()](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD.png)](https://asciinema.org/a/zq5HDcGf2Fp5HcMtRw0ZOSXXD?t=3)


## use in router

```php
Route::get('hi', function () {
    $friends = 'Tinker';

    if (starts_with(Request::ip(), ['192.168', '127'])) {
        eval(tinker());
    }

    return view('fly', compact('friends'));
});
```

## use in view file

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


## tinker abilities 

visit private members, read/write vars, use laravel services and so on.

tinker use examples.

```php
// visit private members
sudo app()->booted
sudo app('router')->routes->actionList 
// Mode Map
sudo $view= app()::$corDict[1]['instances']['view']

// use Model or Controller without writing namespace, thanks to ClassAliasAutoloader
// and the instance is printed beautifully, thanks to casters provided by laravel
$user = User::first()

// like dir() in Python
ls -la $user

// read doc
doc $user->save

// check source code
show $user->query

// use xdebug
xdebug_debug_zval('user')
xdebug_debug_zval('url->routes')
xdebug_call_class()

// magic var
$__file

// check server pid and pidfile
Fly::getServer()
//same as LaravelFly\Fly::getServer()

// which class aliases are defined in tinker
sudo app('tinker')->loader->classes

sudo $middle = Fly::getServer()->kernel->middleware

// run shell commands
`pwd && ls `

```


## Tinker Tips

`eval(tinker())` is a `eval(\Psy\sh())` with extra support for Laravel. It can be used independently without LaravelFly server, but LaravelFly applies the opportunity to use shell online.

There may be a problem with tabcompletion. see [tabcompletion only works "the second time](https://github.com/bobthecow/psysh/issues/435)

If you see an error about permission, please try `sudo chmod -R 0777 ~/.config/psysh`

