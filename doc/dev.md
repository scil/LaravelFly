## Dev starter

1. git clone https://github.com/scil/LaravelFly.git <fly_dev_dir>

2. add following to the composer.json of a project with Laravel
```
  "repositories": [
    {
      "type": "path",
      "url": "<fly_dev_dir>"
    }
  ]
```

3. `cd <fly_dev_dir> && composer install  --prefer-source ` .   
If the project is in a VirtualBox shared dir, it may failed to symlinking to <fly_dev_dir>, the solution is https://www.virtualbox.org/ticket/10085#comment:32  
`--prefer-source` to load laravel/framework/tests ( [How to download excluded paths via composer?](https://stackoverflow.com/questions/28169938/how-to-download-excluded-paths-via-composer) )

## phpunit

1. set this env var in <fly_dev_dir>/phpunit.xml.dist
```
        <env name="LARAVEL_PROJECT_ROOT" value=""/>
```

## Bridge between Laravel and LaravelFly

- overwrite artisan command 'config.cache' which could load laravelfly.php in config dir. code: LaravelFly\Providers\ConfigCacheCommand 

## Update [laravel-fly-files](https://github.com/scil/LaravelFly-fly-files) for updated minor version of Laravel

e.g. updating Laravel 5.5.*

1. create a new project

```
composer create-project --prefer-dist laravel/laravel updating_fly_files "5.5.*"

```

2. edit `vendor/scil/laravel-fly/phpunit.xml.dist`
```xml
        <env name="LARAVEL_VERSION_PROJECT_ROOT" value="/<dir>/updating_fly_files"/>
```

3. run test
```
phpunit=vendor/bin/phpunit
xml=vendor/scil/laravel-fly/phpunit.xml.dist

$phpunit  --stop-on-failure -c $xml --testsuit only_fly

```
