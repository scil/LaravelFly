<?php

namespace LaravelFly\Map\Illuminate\Translation;

class TranslationServiceProvider extends \Illuminate\Translation\TranslationServiceProvider
{

    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            // hack
            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            // hack
            return new FileLoader($app['files'], $app['path.lang']);
        });
    }

}
