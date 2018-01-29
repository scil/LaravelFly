<?php

namespace Illuminate\View\Concerns;

use Illuminate\Support\HtmlString;

trait ManagesComponents
{
    /**
     * The components being rendered.
     *
     * @var array
     */
    protected $componentStack = [];

    /**
     * The original data passed to the component.
     *
     * @var array
     */
    protected $componentData = [];

    /**
     * The slot contents for the component.
     *
     * @var array
     */
    protected $slots = [];

    /**
     * The names of the slots being rendered.
     *
     * @var array
     */
    protected $slotStack = [];

    /**
     * Start a component rendering process.
     *
     * @param  string $name
     * @param  array $data
     * @return void
     */
    public function startComponent($name, array $data = [])
    {
        if (ob_start()) {
            $cid = \Swoole\Coroutine::getuid();
            $this->corDict[$cid]['componentStack'][] = $name;

            $this->corDict[$cid]['componentData'][$this->currentComponent()] = $data;

            $this->corDict[$cid]['slots'][$this->currentComponent()] = [];
        }
    }

    /**
     * Render the current component.
     *
     * @return string
     */
    public function renderComponent()
    {
        $name = array_pop($this->corDict[\Swoole\Coroutine::getuid()]['componentStack']);

        return $this->make($name, $this->componentData($name))->render();
    }

    /**
     * Get the data for the given component.
     *
     * @param  string $name
     * @return array
     */
    protected function componentData($name)
    {
        $cid=\Swoole\Coroutine::getuid();
        return array_merge(
            $this->corDict[$cid]['componentData'][count($this->corDict[$cid]['componentStack'])],
            ['slot' => new HtmlString(trim(ob_get_clean()))],
            $this->corDict[$cid]['slots'][count($this->componentStack)]
        );
    }

    /**
     * Start the slot rendering process.
     *
     * @param  string $name
     * @param  string|null $content
     * @return void
     */
    public function slot($name, $content = null)
    {
        $cid=\Swoole\Coroutine::getuid();
        if (count(func_get_args()) == 2) {
            $this->corDict[$cid]['slots'][$this->currentComponent()][$name] = $content;
        } else {
            if (ob_start()) {
                $this->corDict[$cid]['slots'][$this->currentComponent()][$name] = '';

                $this->corDict[$cid]['slotStack'][$this->currentComponent()][] = $name;
            }
        }
    }

    /**
     * Save the slot content for rendering.
     *
     * @return void
     */
    public function endSlot()
    {
        $cid=\Swoole\Coroutine::getuid();
        last($this->corDict[$cid]['componentStack']);

        $currentSlot = array_pop(
            $this->corDict[$cid]['slotStack'][$this->currentComponent()]
        );

        $this->corDict[$cid]['slots'][$this->currentComponent()]
        [$currentSlot] = new HtmlString(trim(ob_get_clean()));
    }

    /**
     * Get the index for the current component.
     *
     * @return int
     */
    protected function currentComponent()
    {
        return count($this->corDict[\Swoole\Coroutine::getuid()]['componentStack']) - 1;
    }
}
