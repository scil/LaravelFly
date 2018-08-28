<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;
use Illuminate\Contracts\View\View;

trait ManagesLayouts
{

    /**
     * The parent placeholder for the request.
     *
     * @var mixed
     */
    protected static $parentPlaceholder = [];

    /**
     * Start injecting content into a section.
     *
     * @param  string  $section
     * @param  string|null  $content
     * @return void
     */
    public function startSection($section, $content = null)
    {
        if ($content === null) {
            if (ob_start()) {
                static::$corDict[\Co::getUid()]['sectionStack'][] = $section;
            }
        } else {
            $this->extendSection($section, $content instanceof View ? $content : e($content), \Swoole\Coroutine::getuid());
        }
    }

    /**
     * Inject inline content into a section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function inject($section, $content)
    {
        $this->startSection($section, $content);
    }

    /**
     * Stop injecting content into a section and return its contents.
     *
     * @return string
     */
    public function yieldSection()
    {
        if (empty(static::$corDict[\Co::getUid()]['sectionStack'])) {
            return '';
        }

        return $this->yieldContent($this->stopSection());
    }

    /**
     * Stop injecting content into a section.
     *
     * @param  bool  $overwrite
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopSection($overwrite = false)
    {
        $cid=\Co::getUid();
        if (empty(static::$corDict[$cid]['sectionStack'])) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop(static::$corDict[$cid]['sectionStack']);

        if ($overwrite) {
            static::$corDict[$cid]['sections'][$last] = ob_get_clean();
        } else {
            $this->extendSection($last, ob_get_clean(), $cid);
        }

        return $last;
    }

    /**
     * Stop injecting content into a section and append it.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function appendSection()
    {
        $cid=\Co::getUid();
        if (empty(static::$corDict[$cid]['sectionStack'])) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop(static::$corDict[$cid]['sectionStack']);

        if (isset(static::$corDict[$cid]['sections'][$last])) {
            static::$corDict[$cid]['sections'][$last] .= ob_get_clean();
        } else {
            static::$corDict[$cid]['sections'][$last] = ob_get_clean();
        }

        return $last;
    }

    /**
     * Append content to a given section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendSection($section, $content, $cid)
    {
        if (isset(static::$corDict[$cid]['sections'][$section])) {
            $content = str_replace(static::parentPlaceholder($section), $content, static::$corDict[$cid]['sections'][$section]);
        }

        static::$corDict[$cid]['sections'][$section] = $content;
    }

    /**
     * Get the string contents of a section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldContent($section, $default = '')
    {
        $sectionContent = $default instanceof View ? $default : e($default);

        $cid=\Co::getUid();

        if (isset(static::$corDict[$cid]['sections'][$section])) {
            $sectionContent = static::$corDict[$cid]['sections'][$section];
        }

        $sectionContent = str_replace('@@parent', '--parent--holder--', $sectionContent);

        return str_replace(
            '--parent--holder--', '@parent', str_replace(static::parentPlaceholder($section), '', $sectionContent)
        );
    }

    /**
     * Get the parent placeholder for the current request.
     *
     * @param  string  $section
     * @return string
     */
    public static function parentPlaceholder($section = '')
    {
        $cid=\Co::getUid();

        if (! isset(static::$corStaticDict[$cid]['parentPlaceholder'][$section])) {
            static::$corStaticDict[$cid]['parentPlaceholder'][$section] = '##parent-placeholder-'.sha1($section).'##';
        }

        return static::$corStaticDict[$cid]['parentPlaceholder'][$section];
    }

    /**
     * Check if section exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasSection($name)
    {
        return array_key_exists($name, static::$corDict[\Co::getUid()]['sections']);
    }

    /**
     * Get the contents of a section.
     *
     * @param  string  $name
     * @param  string  $default
     * @return mixed
     */
    public function getSection($name, $default = null)
    {
        return $this->getSections()[$name] ?? $default;
    }

    /**
     * Get the entire array of sections.
     *
     * @return array
     */
    public function getSections()
    {
        return static::$corDict[\Co::getUid()]['sections'];
    }

    /**
     * Flush all of the sections.
     *
     * @return void
     */
    public function flushSections()
    {
        $cid=\Co::getUid();
        static::$corDict[$cid]['sections'] = [];
        static::$corDict[$cid]['sectionStack'] = [];
    }
}
