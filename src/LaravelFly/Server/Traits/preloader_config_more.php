<?php
/**
 * from:
https://github.com/laravel/framework/blob/98d01781bf76de817bf15b7f2fed5ba87e0e6f15/src/Illuminate/Foundation/Console/Optimize/config.php
 * the maintain of this file relates two things:
 * 1. the change of laravel
 * 2. the change of \LaravelFly\Fly::$flyMap

 */


$basePath = $this->root;
return [
    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Container/Container.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Foundation/Application.php',

    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Support/Arrayable.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Support/Jsonable.php',

    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Routing/Registrar.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Contracts/Http/Kernel.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Container/Container.php',
    $basePath.'/vendor/symfony/http-kernel/HttpKernelInterface.php',

    // for LARAVELFLY_SERVICES['config']===false
    $basePath . '/vendor/laravel/framework/src/Illuminate/Contracts/Config/Repository.php',
    $basePath . '/vendor/laravel/framework/src/Illuminate/Config/Repository.php',

    // for server config 'log_cache'
//    $basePath . '/vendor/monolog/monolog/src/Monolog/Handler/HandlerInterface.php',
//    $basePath . '/vendor/monolog/monolog/src/Monolog/Handler/AbstractHandler.php',
//    $basePath . '/vendor/monolog/monolog/src/Monolog/Handler/AbstractProcessingHandler.php',
//    $basePath . '/vendor/monolog/monolog/src/Monolog/Handler/StreamHandler.php',

    $basePath . '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesComponents.php',
    $basePath . '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLayouts.php',
    $basePath . '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLoops.php',
    $basePath . '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesStacks.php',
    $basePath . '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesTranslations.php',

    $basePath.'/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/HandleExceptions.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/RegisterFacades.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Support/Traits/Macroable.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/Support/Collection.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/View/ViewFinderInterface.php',
    $basePath.'/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php',
];
