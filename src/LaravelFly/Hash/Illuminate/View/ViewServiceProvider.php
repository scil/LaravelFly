<?php

namespace LaravelFly\Hash\Illuminate\View;

use Illuminate\View\Engines\PhpEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;

// switch for BladeCompiler, use offical or laravelfly
//use Illuminate\View\Compilers\BladeCompiler;

use Illuminate\View\FileViewFinder;

class ViewServiceProvider extends \Illuminate\View\ViewServiceProvider
{

    static public function coroutineFriendlyServices()
    {
        /**
         * Illuminate\View\Engines not rewriten to be A COROUTINE-FRIENDLY SERVICE.
         * so this provider requires app('view.engine.resolver')->register is called on work, and never called in any requests.
         * I think it's rare to call it in a request.
         *
         * @See: Illuminate\View\Engines::register()
         */

        return ['view'];
    }

    protected function createFactory($resolver, $finder, $events)
    {
        return new Factory($resolver, $finder, $events);
    }

    public function registerViewFinder()
    {

        if (!LARAVELFLY_CF_SERVICES['view.finder'])
            include __DIR__ . '/../../../../fly/FileViewFinder.php';

        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], $app['config']['view.paths']);
        });
    }

    /**
     * overwwite laravel offical's 'blade.compiler' to cache view's info
     *
     * @param EngineResolver $resolver
     */
    public function registerBladeEngine($resolver)
    {
        $this->app->singleton('blade.compiler', function () {
            return new BladeCompiler(
                $this->app['files'], $this->app['config']['view.compiled']
            );
        });

        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler']);
        });
    }
}
