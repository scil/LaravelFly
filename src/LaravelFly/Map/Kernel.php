<?php

namespace LaravelFly\Map;

use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
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

        // \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \LaravelFly\Map\Bootstrap\LoadConfiguration::class,

        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

        // \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        // \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \LaravelFly\Map\Bootstrap\RegisterAcrossProviders::class,
        \LaravelFly\Map\Bootstrap\OnWork::class,
        \LaravelFly\Map\Bootstrap\ResolveSomeFacadeAliases::class,
        \LaravelFly\Map\Bootstrap\CleanOnWorker::class,

    ];

    public function handle($request)
    {
        try {
            // moved to LaravelFly\Server\HttpServer::start
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

        // moved to \LaravelFly\Map\Bootstrap\CleanOnWorker. After that, no need to clear in each request.
        // Facade::clearResolvedInstance('request');

        // replace $this->bootstrap();
        $this->app->bootInRequest();

        return (new Pipeline($this->app))
            ->send($request)
            // hack: Cache for kernel middlewares objects.
            // ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->getParsedKernelMiddlewares())
            ->then($this->dispatchToRouter());
    }

    /**
     * hack: Cache for kernel middlewares objects.
     * middlewars are frozened when the first request goes into Pipeline
     * @var array
     */
    static $parsedKernelMiddlewares = [];

    /**
     * hack: Cache for terminateMiddleware objects.
     * only kernel middlewares here
     * @var array
     */
    static $parsedTerminateMiddlewares = [];

    /**
     * @return array
     * hack: Cache for kernel middlewares objects.
     * hack: Cache for terminateMiddleware objects.
     */
    protected function getParsedKernelMiddlewares(): array
    {
        return static::$parsedKernelMiddlewares ?:
            (static::$parsedKernelMiddlewares = $this->app->parseKernelMiddlewares($this->middleware, static::$parsedTerminateMiddlewares));
    }

    /**
     * hack: Cache for terminateMiddleware objects.
     * including kernel middlewares and route middlewares
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Http\Response $response
     * @return void
     */
    protected function terminateMiddleware($request, $response)
    {
        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            // hack
            // $this->gatherRouteMiddleware($request),
            $this->app->gatherRouteTerminateMiddleware($request),

            // $this->middleware
            static::$parsedTerminateMiddlewares
        );

        foreach ($middlewares as $middleware) {
            /**
             * hack: middlewares not only string, maybe objects now,
             */
            if (is_string($middleware)) {
                list($name) = $this->parseMiddleware($middleware);

                $instance = $this->app->make($name);

            } elseif (is_object($middleware)) {
                $instance = $middleware;
            } else {
                continue;
            }

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }


}