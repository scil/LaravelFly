<?php

namespace Illuminate\View\Concerns;

use Countable;
use Illuminate\Support\Arr;

trait ManagesLoops
{

    /**
     * Add new loop to the stack.
     *
     * @param  \Countable|array  $data
     * @return void
     */
    public function addLoop($data)
    {
        $cid=\Swoole\Coroutine::getuid();

        $length = is_array($data) || $data instanceof Countable ? count($data) : null;

        $parent = Arr::last(static::$corDict[$cid]['loopsStack']);

        static::$corDict[$cid]['loopsStack'][] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length ?? null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count(static::$corDict[$cid]['loopsStack']) + 1,
            'parent' => $parent ? (object) $parent : null,
        ];
    }

    /**
     * Increment the top loop's indices.
     *
     * @return void
     */
    public function incrementLoopIndices()
    {
        $cid=\Swoole\Coroutine::getuid();

        $loop = static::$corDict[$cid]['loopsStack'][$index = count(static::$corDict[$cid]['loopsStack']) - 1];

        static::$corDict[$cid]['loopsStack'][$index] = array_merge(static::$corDict[$cid]['loopsStack'][$index], [
            'iteration' => $loop['iteration'] + 1,
            'index' => $loop['iteration'],
            'first' => $loop['iteration'] == 0,
            'remaining' => isset($loop['count']) ? $loop['remaining'] - 1 : null,
            'last' => isset($loop['count']) ? $loop['iteration'] == $loop['count'] - 1 : null,
        ]);
    }

    /**
     * Pop a loop from the top of the loop stack.
     *
     * @return void
     */
    public function popLoop()
    {
        array_pop(static::$corDict[\Swoole\Coroutine::getuid()]['loopsStack']);
    }

    /**
     * Get an instance of the last loop in the stack.
     *
     * @return \stdClass|null
     */
    public function getLastLoop()
    {
        if ($last = Arr::last(static::$corDict[\Swoole\Coroutine::getuid()]['loopsStack'])) {
            return (object) $last;
        }
    }

    /**
     * Get the entire loop stack.
     *
     * @return array
     */
    public function getLoopStack()
    {
        return static::$corDict[\Swoole\Coroutine::getuid()]['loopsStack'];
    }
}
