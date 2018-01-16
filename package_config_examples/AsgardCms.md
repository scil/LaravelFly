
AsgardCms is a modular multilingual CMS built with Laravel 5

## install AsgardCms
https://asgardcms.com/install

## edit config/laravelfly.php
1 add its ServiceProvider name to the `providers_in_request` array
```
Modules\Core\Providers\AsgardServiceProvider::class,
```

2 if you try Greedy mode, in the `providers_in_worker` array ,make sure `paths` and `views` under `view.__obj__.finder` are uncommented.
```
'view' => [
     'obj.finder' => [
          'paths',
          'views',
    ],
],
```
To backup them is often necessary ,as Asgard frontend and backend use different Themes by default . 

## asgard/app/Providers/AppServiceProvider.php 
comment this line:
```
		if ($this->app->environment() == 'local') {
			$this->app->register('Barryvdh\Debugbar\ServiceProvider');
		}
```
If you'd like to use Debugbar, please follow the steps in [Debugbar.md](Debugbar.md)

## Final
All of AsgardCms services are hard to registered or booted on work. All of them are registered and booted in each request. So AsgardCms can't use LaravelFly power. I use AsgardCms to make sure Laravel works fine on LaravelFly.


