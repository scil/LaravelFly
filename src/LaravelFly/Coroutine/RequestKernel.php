<?php

namespace LaravelFly\Coroutine;

use Illuminate\Foundation\Http\Events;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class RequestKernel extends HttpKernel
{
    /**
     * The application implementation.
     *
     * @var \LaravelFly\Coroutine\RequestApplication
     */
    protected $app;

    protected $bootstrappers = [
    ];

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

//        $this->bootstrap();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }
}