<?php
/**
 * User: scil
 * Date: 2018/9/9
 * Time: 10:10
 */

namespace LaravelFly\Map\Illuminate\Translation;


use Illuminate\Contracts\Translation\Loader;
use LaravelFly\Map\Util\Dict;

class Translator extends \Illuminate\Translation\Translator
{
    use Dict;

    protected static $normalAttriForObj = [
        // 'locale' => null,  // see below
    ];


    public function __construct(Loader $loader, $locale)
    {
        $this->loader = $loader;
        static::$normalAttriForObj = [
            'locale' => $locale,
        ];

        $this->initOnWorker(true);
    }

    public function setLocale($locale)
    {
        static::$corDict[\Co::getUid()]['locale'] = $locale;
    }

    public function getLocale()
    {
        return static::$corDict[\Co::getUid()]['locale'];
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        $locales = $fallback ? $this->localeArray($locale)
            : [$locale ?: static::$corDict[\Co::getUid()]['locale']];// hack

        foreach ($locales as $locale) {
            if (!is_null($line = $this->getLine(
                $namespace, $group, $locale, $item, $replace
            ))) {
                break;
            }
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        if (isset($line)) {
            return $line;
        }

        return $key;
    }


    public function getFromJson($key, array $replace = [], $locale = null)
    {
        // hack
        $locale = $locale ?: static::$corDict[\Co::getUid()]['locale'];

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
        if (!isset($line)) {
            $fallback = $this->get($key, $replace, $locale);

            if ($fallback !== $key) {
                return $fallback;
            }
        }

        return $this->makeReplacements($line ?: $key, $replace);
    }

    protected function localeArray($locale)
    {
        return array_filter([$locale ?: static::$corDict[\Co::getUid()]['locale'], $this->fallback]);
    }


    protected function localeForChoice($locale)
    {
        return $locale ?: static::$corDict[\Co::getUid()]['locale'] ?: $this->fallback;
    }
}