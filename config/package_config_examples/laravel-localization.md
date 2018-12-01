
[mcamara/laravel-localization](https://github.com/mcamara/laravel-localization)


package | solutions| involved
 ---- | --- | -----
 [mcamara/laravel-localization](https://github.com/scil/LaravelFly/blob/master/config/package_config_examples/laravel-localization.md) <br> ([offical](https://github.com/mcamara/laravel-localization) ) | 1. across (no coroutine) <br> 2. clone (no cor) <br> 3. clone and include routes on worker (no cor) | - routes/web.php  <br> - app('request')  <br>

## Prepearation

check some functions, according by [Checklist For Safety](https://github.com/scil/LaravelFly/wiki/Checklist-For-Safety)

``` 

package_dir=vendor/mcamara/laravel-localization/src

# find out: exit( die( header( setcookie( setrawcookie( session_start http_response_code
# but ignore: >header // e.g. $request->header('Accept-Language')
grep  -H -n -r -E "\bexit\(|\bdie\(|[^>]header\(|\bsetcookie\(|\bsetrawcookie\(|\bsession_start\(|\bhttp_response_code\("  $package_dir 

grep  -H -n -r -E "\bflush\(|\bob_flush\("  $package_dir 
grep  -H -n -r -E "\binclude_once\(|\brequire_once\("  $package_dir 

grep  -H -n -r -E "\bini_set\(|\bsetlocale\(|\bset_include_path\(|\bset_exception_handler\(|\bset_error_handler\("  $package_dir
#output:
# vendor/mcamara/laravel-localization/src/Mcamara/LaravelLocalization/LaravelLocalization.php:184:            setlocale(LC_TIME, $regional . $suffix);
# vendor/mcamara/laravel-localization/src/Mcamara/LaravelLocalization/LaravelLocalization.php:185:            setlocale(LC_MONETARY, $regional . $suffix);
#so:
# It's better not to use coroutine.


```

# Solution 1: leave it a cross service and do not use coroutine

<details>
<summary>Details</summary>
<div>

### Non-allowed functions

[x] no

### Non-allowed functions in some cases

[x] (no)

### no coroutine

[x]. ensure `const LARAVELFLY_COROUTINE = false; ` in fly.conf.php 

[x] `setlocale()` is used at the beginning of each request, so restore is not needed with no coroutine used.

### Across service provider

[x] routes/web.php uses this package 

</div>
</details>

```
   // config/laravelfly.php

    'providers_on_worker' => [
    
       ....
    
        // this provider loads `routes/web.php` which uses LaravelLocalization 
        App\Providers\RouteServiceProvider::class => 'across',
        
    ],
    

```

# Solution 2: clone

mores steps based on Solution 1

<details>
<summary>Details</summary>
<div>

[x] put service provides into 'providers_on_worker'

[x] all middlewares can be think as WORKER SERVICE ( this prop not change in my project: `protected $except = [];` )
    
[x] list singleton services providers by this packages: `Mcamara\LaravelLocalization\LaravelLocalization::class`

[x] add `clone` to this singleton service

[x] NO ref in other services

[x] NO ref to this service in controllers

[x] NO static props

</div>
</details>

1. 
```
   // config/laravelfly.php

    'providers_on_worker' => [
    
       ....
    
        Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class=>[
            Mcamara\LaravelLocalization\LaravelLocalization::class => 'clone',
        ],
        
    ],
    
    
    
    'update_on_request' => [
    ],

    'singleton_route_middlewares' => [
        ...
        
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class
        
    ],

```

2. If there are be references to app('laravellocalization') in any CLONE SERVICE or WORKER SERVICE, please update them.


# Solution 3: clone and loads routes/web.php on work

more steps bases on Solution 2.

value: routes are parsed and cache can be used.

1. Do not use Caching routes provided by laravel-localization (php artisan route:trans:cache),   
because LaravelFly will load all routes for all locales.

2. config/laravelfly.php
   ```

    'providers_on_worker' => [
    
       ....
    
        // change 'across'
        App\Providers\RouteServiceProvider::class => [],
        
    ],
    
    ```

3. define routes for all locales in routes/web.php
``` 

$routesForAllLocal = function () {
	/** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
	Route::get('/', function()
	{
		return View::make('hello');
	});

	Route::get('test',function(){
		return View::make('test');
	});
}

if (defined('LARAVELFLY_MODE')) {
    $locales = LaravelLocalization::getSupportedLanguagesKeys();
} else {
    $locales = [LaravelLocalization::setLocale()];
}


foreach ($locales as $locale) {

    Route::group(
      [
        'prefix' => $locale,
        'middleware' => [ 
          function ($request, Closure $next) {LaravelLocalization::setLocale();return $next($request);},
          'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' 
        ]
      ],
      $routesForAllLocal);

}
```

Pay attention to the Closure middleware 
```
          function ($request, Closure $next) {LaravelLocalization::setLocale();return $next($request);},
```

It's used to play the role of `LaravelLocalization::setLocale()` which is suggested to used by [laravel-localization](https://github.com/mcamara/laravel-localization#usage) official.

If LaravelLocalization API is used in other routes/actions except $routesForAllLocal, please put this Closure middleware in `App\Http\Kernel::$middleware`
 
