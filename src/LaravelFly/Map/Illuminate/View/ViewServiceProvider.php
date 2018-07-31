<?php

namespace LaravelFly\Map\Illuminate\View;

use Illuminate\View\Engines\PhpEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

use Illuminate\View\FileViewFinder;

class ViewServiceProvider extends \Illuminate\View\ViewServiceProvider
{

    static public function coroutineFriendlyServices(): array
    {
        /**
         * Illuminate\View\Engines not rewriten to be A COROUTINE-FRIENDLY SERVICE.
         * so this provider requires app('view.engine.resolver')->register is called on work, and never called in any requests.
         * I think it's rare to call it in a request.
         *
         * @See: Illuminate\View\Engines::register()
         */

        return ['view', 'view.engine.resolver', 'blade.compiler'];
    }

    protected function createFactory($resolver, $finder, $events)
    {
        // hack
        return new Factory($resolver, $finder, $events);
    }


    /**
     * overwwite laravel offical's 'blade.compiler' to cache view's info
     *
     * @param EngineResolver $resolver
     */
    public function registerBladeEngine($resolver)
    {
        $this->app->singleton('blade.compiler', function () {
            if(config('laravelfly.view_compile_1'))
                // hack: Cache for view compiled path.
                return new BladeCompiler_1(
                    $this->app['files'], $this->app['config']['view.compiled']
                );

            return new BladeCompiler(
                $this->app['files'], $this->app['config']['view.compiled']
            );
        });

        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler']);
        });
    }
}
