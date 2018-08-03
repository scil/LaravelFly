<?php
/**
 * Dict
 * plus
 * gatherRouteTerminateMiddleware // search 'hack' in this file
 */

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
    use \LaravelFly\Map\Util\Dict;
    protected static $normalAttriForObj = [];
    protected static $arrayAttriForObj = ['middleware'];

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


    /*  coroutine start. This part only for coroutine */

    public function __construct(\Illuminate\Contracts\Foundation\Application $app, Router $router)
    {
        parent::__construct($app, $router);

        $this->initOnWorker(true);

        static::$corDict[WORKER_COROUTINE_ID]['middleware'] = $this->middleware;
    }

    public function hasMiddleware($middleware)
    {
        return in_array($middleware, static::$corDict[\Co::getUid()]['middleware']);
    }

    public function prependMiddleware($middleware)
    {
        if (array_search($middleware, static::$corDict[\Co::getUid()]['middleware']) === false) {
            array_unshift(static::$corDict[\Co::getUid()]['middleware'], $middleware);
        }

        return $this;
    }

    public function pushMiddleware($middleware)
    {
        if (array_search($middleware, static::$corDict[\Co::getUid()]['middleware']) === false) {
            static::$corDict[\Co::getUid()]['middleware'][] = $middleware;
        }

        return $this;
    }

    /*  coroutine END */


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

        // moved to \LaravelFly\Map\Bootstrap\CleanOnWorker. After that, no much need to clear in each request.
        // Facade::clearResolvedInstance('request');

        // replace $this->bootstrap();
        $this->app->bootInRequest();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : static::$corDict[\Co::getUid()]['middleware'])
            ->then($this->dispatchToRouter());
    }

    //hack
    protected function terminateMiddleware($request, $response)
    {
        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            // hack
            // $this->gatherRouteMiddleware($request),
            $this->app->gatherRouteTerminateMiddleware($request),

            // $this->middleware
            // no cache for kernel middlewares when !LARAVELFLY_SERVICES['kernel'], it's different from src/LaravelFly/Map/Kernel.php
            static::$corDict[\Co::getUid()]['middleware']
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