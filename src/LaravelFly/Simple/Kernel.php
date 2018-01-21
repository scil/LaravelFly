<?php

namespace LaravelFly\Simple;


use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Foundation\Http\Events;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        // must placed before RegisterProviders, because it change config('app.providers')
        \LaravelFly\Simple\Bootstrap\CleanProviders::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,

        // replaced by `$this->app->bootProvidersInRequest();`
        // \Illuminate\Foundation\Bootstrap\BootProviders::class,

        \LaravelFly\Simple\Bootstrap\SetBackupForBaseServices::class,
        \LaravelFly\Simple\Bootstrap\BackupConfigs::class,
        \LaravelFly\Simple\Bootstrap\BackupAttributes::class,
    ];
    /**
     * The application implementation.
     *
     * @var \LaravelFly\Simple\Application
     */
    protected $app;

    /**
     * Override
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        // moved to Application::restoreAfterRequest
        // Facade::clearResolvedInstance('request');

        // replace $this->bootstrap();
        $this->app->boot();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());

    }
    /**
     * Override
     */
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


}