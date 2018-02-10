<?php

namespace Illuminate\View\Concerns;

trait ManagesTranslations
{

    /**
     * Start a translation block.
     *
     * @param  array  $replacements
     * @return void
     */
    public function startTranslation($replacements = [])
    {
        ob_start();

        static::$corDict[\Swoole\Coroutine::getuid()]['translationReplacements'] = $replacements;
    }

    /**
     * Render the current translation.
     *
     * @return string
     */
    public function renderTranslation()
    {
        return $this->container->make('translator')->getFromJson(
            trim(ob_get_clean()), static::$corDict[\Swoole\Coroutine::getuid()]['translationReplacements']
        );
    }
}
