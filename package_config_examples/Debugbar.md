## config/app.php
add the ServiceProvider to the providers array
```
Barryvdh\Debugbar\ServiceProvider::class,
```

## config/laravelfly.php
1 add config 'debugbar.enabled' to the `config_need_backup` array
```
'debugbar.enabled',
```
Debugbar is disabled after booting, so it's necessary to restore this config after each request.


2 If your Debugbar version <=v2.0.5, add its ServiceProvider name to the `providers_in_request` array
```
Barryvdh\Debugbar\ServiceProvider::class,
```
Debugbar <=v2.0.5 is registered using share,not singleson .It's hard to delete shared services ,so it's necessary to put debugbar register in request.
If your debugbar version >=2.0.6, do NOT to config this.

## laravelfly.server.php
```
const FAKE_NOT_IN_CONSOLE = true; 
```
Debugbar will not be enalbed when in cli mode.
Related code:  "if ($app->runningInConsole()) {"  at vendor/barryvdh/laravel-debugbar/src/ServiceProvider.php

## Final
Now Debugbar seems to be working well.But i'm not sure if its data (Timeline, Memory Usage and so on) is correct .

