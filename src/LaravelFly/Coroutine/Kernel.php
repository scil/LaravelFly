<?php

namespace LaravelFly\Coroutine;

use Illuminate\Container\Container;
use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Http\Events;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

use Illuminate\Routing\Router;
use Illuminate\Contracts\Debug\ExceptionHandler;

class Kernel extends HttpKernel
{
    /**
     * The application implementation.
     *
     * @var \LaravelFly\Coroutine\Application
     */
    protected $app;

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
//        \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \LaravelFly\Coroutine\Bootstrap\ReadProvidersConfig::class,
        \LaravelFly\Coroutine\Bootstrap\RegisterAcrossProviders::class,
        \LaravelFly\Coroutine\Bootstrap\RegisterAndBootProvidersOnWork::class,
        \LaravelFly\Coroutine\Bootstrap\ResetServiceProviders::class,

        //todo
//        \LaravelFly\Greedy\Bootstrap\FindViewFiles::class,

    ];

    function __clone()
    {
        $this->app = Container::getInstance();
        //todo the new obj should update app->instances
        //todo routers?
    }

    public function handle($request)
    {
        try {
            // moved to LaravelFlyServer::initSthWhenServerStart
            // $request::enableHttpMethodParameterOverride();

            $response = $this->sendRequestThroughRouter($request);

        } catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {

            $this->reportException($e = new FatalThrowableError($e));

            $response = $this->renderException($request, $e);
        }

        $this->app['events']->dispatch(
            new Events\RequestHandled($request, $response)
        );

        return $response;
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        // todo
        Facade::clearResolvedInstance('request');

        // replace $this->bootstrap();
        $this->app->bootInRequest();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }
    protected function dispatchToRouter()
    {
        return function ($request) {
            //todo
            //?  why?  request has been inserted
            $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }
}