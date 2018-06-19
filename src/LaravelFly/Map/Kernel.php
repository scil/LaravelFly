<?php

namespace LaravelFly\Map;

use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Http\Events;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application implementation.
     *
     * @var \LaravelFly\Map\Application
     */
    protected $app;

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
//        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \LaravelFly\Map\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
//        \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \LaravelFly\Map\Bootstrap\RegisterAcrossProviders::class,
        \LaravelFly\Map\Bootstrap\RegisterAndBootProvidersOnWork::class,
        \LaravelFly\Map\Bootstrap\ResolveSomeFacadeAliases::class,
        \LaravelFly\Map\Bootstrap\ResetServiceProviders::class,

    ];

    function __clone()
    {
        $this->router = $this->app->make('router');
        $this->app->instance(\Illuminate\Contracts\Http\Kernel::class, $this);
    }

    public function handle($request)
    {
        try {
            // moved to LaravelFlyServer::initAfterStart
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