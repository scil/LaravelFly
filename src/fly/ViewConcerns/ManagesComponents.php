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
            $cid = \co::getUid();
            static::$corDict[$cid]['componentStack'][] = $name;

            static::$corDict[$cid]['componentData'][$this->currentComponent($cid)] = $data;

            static::$corDict[$cid]['slots'][$this->currentComponent($cid)] = [];
        }
    }

    /**
     * Render the current component.
     *
     * @return string
     */
    public function renderComponent()
    {
        $cid = \co::getUid();

        $name = array_pop(static::$corDict[$cid]['componentStack']);

        return $this->make($name, $this->componentData($name, $cid))->render();
    }

    /**
     * Get the data for the given component.
     *
     * @param  string $name
     * @return array
     */
    protected function componentData($name, $cid)
    {
        return array_merge(
            static::$corDict[$cid]['componentData'][count(static::$corDict[$cid]['componentStack'])],
            ['slot' => new HtmlString(trim(ob_get_clean()))],
            static::$corDict[$cid]['slots'][count($this->componentStack)]
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
        $cid = \co::getUid();
        if (count(func_get_args()) === 2) {
            static::$corDict[$cid]['slots'][$this->currentComponent($cid)][$name] = $content;
        } else {
            if (ob_start()) {
                static::$corDict[$cid]['slots'][$this->currentComponent($cid)][$name] = '';

                static::$corDict[$cid]['slotStack'][$this->currentComponent($cid)][] = $name;
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
        $cid = \co::getUid();
        last(static::$corDict[$cid]['componentStack']);

        $currentSlot = array_pop(
            static::$corDict[$cid]['slotStack'][$this->currentComponent($cid)]
        );

        static::$corDict[$cid]['slots'][$this->currentComponent($cid)]
        [$currentSlot] = new HtmlString(trim(ob_get_clean()));
    }

    /**
     * Get the index for the current component.
     *
     * @return int
     */
    protected function currentComponent($cid)
    {
        return count(static::$corDict[$cid]['componentStack']) - 1;
    }
}
