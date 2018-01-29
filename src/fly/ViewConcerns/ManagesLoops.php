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

        $parent = Arr::last($this->corDict[$cid]['loopsStack']);

        $this->corDict[$cid]['loopsStack'][] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length ?? null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->corDict[$cid]['loopsStack']) + 1,
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

        $loop = $this->corDict[$cid]['loopsStack'][$index = count($this->loopsStack) - 1];

        $this->corDict[$cid]['loopsStack'][$index] = array_merge($this->loopsStack[$index], [
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
        array_pop($this->corDict[\Swoole\Coroutine::getuid()]['loopsStack']);
    }

    /**
     * Get an instance of the last loop in the stack.
     *
     * @return \stdClass|null
     */
    public function getLastLoop()
    {
        if ($last = Arr::last($this->corDict[\Swoole\Coroutine::getuid()]['loopsStack'])) {
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
        return $this->corDict[\Swoole\Coroutine::getuid()]['loopsStack'];
    }
}
