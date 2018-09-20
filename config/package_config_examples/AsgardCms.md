
[AsgardCms](https://github.com/AsgardCms/Platform) is a modular multilingual CMS built with Laravel 5

## install AsgardCms
https://asgardcms.com/install

## config/laravelfly.php
1 add its ServiceProvider name to the `providers_in_request` array
```
Modules\Core\Providers\AsgardServiceProvider::class,
```

## hack
edit asgard/app/Providers/AppServiceProvider.php 

comment these lines to stop AsgardCms enable Debugbar:
```
		if ($this->app->environment() == 'local') {
			$this->app->register('Barryvdh\Debugbar\ServiceProvider');
		}
```

## Final
All of AsgardCms services are hard to registered or booted on WorkerStart. All of them are registered and booted in each request. So AsgardCms can't use LaravelFly power. I use AsgardCms to make sure Laravel works fine on LaravelFly.


