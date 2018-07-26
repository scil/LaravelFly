<?php

namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Repository implements ArrayAccess, ConfigContract
{
    use \LaravelFly\Map\Util\Dict {
    }
    protected static $normalAttriForObj = [];
    protected static $arrayAttriForObj = ['items'];

    /**
     * All of the configuration items.
     *
     * @var array
     */
//    protected $items = [];

    /**
     * Create a new configuration repository.
     *
     * @param  array $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->initOnWorker(true);

        static::$corDict[WORKER_COROUTINE_ID]['items'] = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has(static::$corDict[\Co::getUid()]['items'], $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get(static::$corDict[\Co::getUid()]['items'] , $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                list($key, $default) = [$default, null];
            }

            $config[$key] = Arr::get(static::$corDict[\Co::getUid()]['items'], $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string $key
     * @param  mixed $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set(static::$corDict[\Co::getUid()]['items'], $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return static::$corDict[\Co::getUid()]['items'];
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
