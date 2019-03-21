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

e.g. updating Laravel 5.7.28

1. create a new project 

```
mkdir updating
cd updating
vi composer.json

```

edit 'updating/composer.json'
```
  "require": {
    ...
    "laravel/framework": "5.7.*",
    "scil/laravel-fly": "dev-master"
  },
"autoload-dev": {
    "psr-4": {
        ...
        "LaravelFly\\Tests\\": "vendor/scil/laravel-fly/tests/"
    }
},
  "repositories": [
    {
      "type": "path",
      "url": "vendor/scil/laravel-fly-files-local"
    }
  ]

```

then 
```
composer install
cp -R vendor/scil/laravel-fly-files vendor/scil/laravel-fly-files-local
```

2. edit `vendor/scil/laravel-fly-files-local/composer.json`
```
    "laravel/framework": "5.7.28"
```

3. edit `vendor/scil/laravel-fly/phpunit.xml.dist`
```xml
        <env name="LARAVEL_VERSION_PROJECT_ROOT" value="./../../../../updating"/>
```

4. update then run test at laravel project root
```
composer update

phpunit=vendor/bin/phpunit
xml=vendor/scil/laravel-fly/phpunit.xml.dist

$phpunit  --stop-on-failure -c $xml --testsuit only_fly

```
