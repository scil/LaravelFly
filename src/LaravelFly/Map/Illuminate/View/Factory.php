<?php

namespace LaravelFly\Map\Illuminate\View;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\Concerns\ManagesLayouts;
use Illuminate\View\ViewFinderInterface;
use InvalidArgumentException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as FactoryContract;
use LaravelFly\Map\Util\Dict;
use LaravelFly\Map\Util\StaticDict;

class Factory extends \Illuminate\View\Factory
{
    use Dict;
    use StaticDict;

    protected static $normalAttriForObj = ['renderCount' => 0];

    protected static $arrayAttriForObj = ['shared',
        // ManagesLayouts
        'sections', 'sectionStack',
        // ManagesComponents
        'componentStack', 'componentData', 'slots', 'slotStack',
        // ManagesLoops
        'loopsStack',
        // ManagesStacks
        'pushes', 'prepends', 'pushStack',
        // ManagesTranslations
        'translationReplacements',
    ];

    protected static $arrayStaticAttri = [
        // ManagesLayouts
        'parentPlaceholder'];

    public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->initOnWorker( true);
        static::initStaticForCorontine(-1, true);

        // this line must be the last, because it visit share
        parent::__construct($engines, $finder, $events);
    }

    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        $cid = \co::getUid();
        foreach ($keys as $key => $value) {
            static::$corDict[$cid]['shared'][$key] = $value;
        }

        return $value;
    }

    public function shared($key, $default = null)
    {
        return Arr::get(static::$corDict[\co::getUid()]['shared'], $key, $default);
    }

    public function getShared()
    {
        return static::$corDict[\co::getUid()]['shared'];
    }

    public function incrementRender()
    {
        static::$corDict[\co::getUid()]['renderCount']++;
    }

    public function decrementRender()
    {
        static::$corDict[\co::getUid()]['renderCount']--;
    }

    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering()
    {
        return static::$corDict[\co::getUid()]['renderCount'] == 0;
    }

    public function flushState()
    {
        static::$corDict[\co::getUid()]['renderCount'] = 0;

        $this->flushSections();
        $this->flushStacks();
    }
}

