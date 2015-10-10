<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/7/28
 * Time: 21:15
 */

namespace LaravelFly;


use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel extends \App\Http\Kernel
{

    protected $bootstrappers = [
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'Illuminate\Foundation\Bootstrap\LoadConfiguration',
        'Illuminate\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'LaravelFly\Bootstrap\SetProvidersInRequest',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',

        // move sp boot to `$this->app->bootProvidersInRequest();`
        // 'Illuminate\Foundation\Bootstrap\BootProviders',

        'LaravelFly\Bootstrap\MakeAndSetBackupForServicesInWorker',

        'LaravelFly\Bootstrap\BackupConfigs',
        'LaravelFly\Bootstrap\BackupAttributes',
    ];

    /**
     * Override
     */
    public function handle($request)
    {
        try {

            $this->app->instance('request', $request);

            // moved to Application::restoreAfterRequest
            // Facade::clearResolvedInstance('request');

            $this->app->registerConfiguredProvidersInRequest();
            $this->app->bootProvidersInRequest();

            $shouldSkipMiddleware = $this->app->bound('middleware.disable') &&
                $this->app->make('middleware.disable') === true;

            $response =
                (new Pipeline($this->app))
                ->send($request)
                ->through($shouldSkipMiddleware ? [] : $this->middleware)
                ->then($this->dispatchToRouter());

        } catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $e = new FatalThrowableError($e);

            $this->reportException($e);

            $response = $this->renderException($request, $e);
        }


        $this->app->make('events')->fire('kernel.handled', [$request, $response]);

        return $response;
    }

}