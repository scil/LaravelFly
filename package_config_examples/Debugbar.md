## edit config/app.php
add the ServiceProvider to the providers array
```
Barryvdh\Debugbar\ServiceProvider::class,
```

## edit config/laravelfly.php
1 add 'debugbar.enabled' to the `config_changed_in_requests` array
```
'debugbar.enabled',
```
Debugbar is disabled after booting, so it's necessary to maintain this config for each request.


2 If your Debugbar version <=v2.0.5, add its ServiceProvider name to the `providers_in_request` array
```
Barryvdh\Debugbar\ServiceProvider::class,
```
Debugbar <=v2.0.5 is registered using share,not singleson .It's hard to delete shared services ,so it's necessary to put debugbar register in request.
If your debugbar version >=2.0.6, do NOT to config this.

## edit laravelfly.server.config.php
```
const HONEST_IN_CONSOLE = false; 
```
Debugbar will not be enalbed when in cli mode.
Related code:  "if ($app->runningInConsole()) {"  at vendor/barryvdh/laravel-debugbar/src/ServiceProvider.php

## Final
Now Debugbar seems to be working well.But i'm not sure if its data (Timeline, Memory Usage and so on) is correct .

