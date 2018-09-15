
[mcamara/laravel-localization](https://github.com/mcamara/laravel-localization)

``` 

package_dir=vendor/mcamara/laravel-localization/src

# find out: exit( die( header( setcookie( setrawcookie( session_start http_response_code
# but ignore: >header // e.g. $request->header('Accept-Language')
grep  -H -n -r -E "\bexit\(|\bdie\(|[^>]header\(|\bsetcookie\(|\bsetrawcookie\(|\bsession_start\(|\bhttp_response_code\("  $package_dir 

grep  -H -n -r -E "\bflush\(|\bob_flush\("  $package_dir 
grep  -H -n -r -E "\binclude_once\(|\brequire_once\("  $package_dir 

grep  -H -n -r -E "\bini_set\(|\bsetlocale\(|\bset_include_path\(|\bset_exception_handler\(|\bset_error_handler\("  $package_dir
# output:
# vendor/mcamara/laravel-localization/src/Mcamara/LaravelLocalization/LaravelLocalization.php:184:            setlocale(LC_TIME, $regional . $suffix);
# vendor/mcamara/laravel-localization/src/Mcamara/LaravelLocalization/LaravelLocalization.php:185:            setlocale(LC_MONETARY, $regional . $suffix);


        

```

## Simplest Solution: keep it a cross service and do not use coroutine

### non-allowed functions

(no)

### non-allowed functions in some cases

(no)

### do not use coroutine

1. ensure `const LARAVELFLY_COROUTINE = false; ` in fly.conf.php 

~~2. `setlocale()` is used at the beginning of each request, so restore is not needed.~~

### Across service providers

1. config/laravelfly.php
   ```

    'providers_on_worker' => [
    
       ....
    
        // this provider loads `routes/web.php` which uses LaravelLocalization 
        App\Providers\RouteServiceProvider::class => 'across',
        
    ],
    

    ```

## Solution 2: clone

mores steps based on Solution 1

1. config/laravelfly.php
   ```

    'providers_on_worker' => [
    
       ....
    
        Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class=>[
            Mcamara\LaravelLocalization\LaravelLocalization::class => 'clone',
        ],
        
    ],
    
    
    'clean_Facade_on_work' => [
        ....
        
        'laravellocalization',
    ],
    
    
    'update_on_request' => [

        [
            'this' => 'laravellocalization',
            'closure' => function () {
                app()->rebinding('request', function () {
                    $this->request = app('request');
                });
            }
        ],
    ],

    ```

2. There may be references to app('laravellocalization') in any CLONE SERVICE or WORKER SERVICE, please update them if necessary.

## Solution 3: clone and loads routes/web.php on work

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

Pay attension to the Closure middleware 
```
          function ($request, Closure $next) {LaravelLocalization::setLocale();return $next($request);},
```

It's used to play the role of `LaravelLocalization::setLocale()` which is suggested to used by [laravel-localization](https://github.com/mcamara/laravel-localization#usage) official.

If LaravelLocalization API is used in other routes/actions except $routesForAllLocal, please put this Closure middleware in `App\Http\Kernel::$middleware`
 
