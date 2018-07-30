<?php

namespace LaravelFly\Map;

use Exception;
use Illuminate\Routing\Router;
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
        \LaravelFly\Map\Bootstrap\ProvidersAndServicesOnWork::class,
        \LaravelFly\Map\Bootstrap\ResolveSomeFacadeAliases::class,
        \LaravelFly\Map\Bootstrap\ResetServiceProviders::class,

    ];


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

        // moved to  LaravelFly\Server\Traits\Laravel::startLaravel. After that, no need to clear in each request.
        // Facade::clearResolvedInstance('request');

        // replace $this->bootstrap();
        $this->app->bootInRequest();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }

}