
AsgardCms is a modular multilingual CMS built with Laravel 5

## install AsgardCms
https://asgardcms.com/install

## edit config/laravelfly.php
1 add its ServiceProvider name to the `providers_in_request` array
```
Modules\Core\Providers\AsgardServiceProvider::class,
```

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


