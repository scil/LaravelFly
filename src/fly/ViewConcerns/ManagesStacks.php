<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;

trait ManagesStacks
{
    /**
     * Start injecting content into a push section.
     *
     * @param  string $section
     * @param  string $content
     * @return void
     */
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                static::$corDict[\Swoole\Coroutine::getuid()]['pushStack'][] = $section;
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    /**
     * Stop injecting content into a push section.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopPush()
    {
        $cid = \Swoole\Coroutine::getuid();

        if (empty(static::$corDict[$cid]['pushStack'])) {
            throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
        }

        return tap(array_pop(static::$corDict[$cid]['pushStack']), function ($last) {
            $this->extendPush($last, ob_get_clean());
        });
    }

    /**
     * Append content to a given push section.
     *
     * @param  string $section
     * @param  string $content
     * @return void
     */
    protected function extendPush($section, $content)
    {
        $cid = \Swoole\Coroutine::getuid();

        if (!isset(static::$corDict[$cid]['pushes'][$section])) {
            static::$corDict[$cid]['pushes'][$section] = [];
        }

        if (!isset(static::$corDict[$cid]['pushes'][$section][$this->renderCount])) {
            static::$corDict[$cid]['pushes'][$section][$this->renderCount] = $content;
        } else {
            static::$corDict[$cid]['pushes'][$section][$this->renderCount] .= $content;
        }
    }

    /**
     * Start prepending content into a push section.
     *
     * @param  string $section
     * @param  string $content
     * @return void
     */
    public function startPrepend($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                static::$corDict[\Swoole\Coroutine::getuid()]['pushStack'][] = $section;
            }
        } else {
            $this->extendPrepend($section, $content);
        }
    }

    /**
     * Stop prepending content into a push section.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopPrepend()
    {
        $cid = \Swoole\Coroutine::getuid();

        if (empty(static::$corDict[$cid]['pushStack'])) {
            throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
        }

        return tap(array_pop(static::$corDict[$cid]['pushStack']), function ($last) {
            $this->extendPrepend($last, ob_get_clean());
        });
    }

    /**
     * Prepend content to a given stack.
     *
     * @param  string $section
     * @param  string $content
     * @return void
     */
    protected function extendPrepend($section, $content)
    {
        $cid = \Swoole\Coroutine::getuid();

        if (!isset(static::$corDict[$cid]['prepends'][$section])) {
            static::$corDict[$cid]['prepends'][$section] = [];
        }

        if (!isset(static::$corDict[$cid]['prepends'][$section][$this->renderCount])) {
            static::$corDict[$cid]['prepends'][$section][$this->renderCount] = $content;
        } else {
            static::$corDict[$cid]['prepends'][$section][$this->renderCount] = $content . $this->prepends[$section][$this->renderCount];
        }
    }

    /**
     * Get the string contents of a push section.
     *
     * @param  string $section
     * @param  string $default
     * @return string
     */
    public function yieldPushContent($section, $default = '')
    {
        $cid = \Swoole\Coroutine::getuid();

        if (!isset(static::$corDict[$cid]['pushes'][$section]) && !isset($this->prepends[$section])) {
            return $default;
        }

        $output = '';

        if (isset(static::$corDict[$cid]['prepends'][$section])) {
            $output .= implode(array_reverse(static::$corDict[$cid]['prepends'][$section]));
        }

        if (isset(static::$corDict[$cid]['pushes'][$section])) {
            $output .= implode(static::$corDict[$cid]['pushes'][$section]);
        }

        return $output;
    }

    /**
     * Flush all of the stacks.
     *
     * @return void
     */
    public function flushStacks()
    {
        $cid = \Swoole\Coroutine::getuid();
        static::$corDict[$cid]['pushes'] = [];
        static::$corDict[$cid]['prepends'] = [];
        static::$corDict[$cid]['pushStack'] = [];
    }
}
