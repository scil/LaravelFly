<?php
/**
 * User: scil
 * Date: 2018/8/20
 * Time: 10:15
 */

namespace LaravelFly\Server\Traits;

use swoole_atomic;

trait ShareInteger
{
    /**
     * @var [\swoole_atomic] save shared actomic info across processes
     */
    var $integerMemory = [];

    public function addIntegerMemory(string $name, swoole_atomic $atom)
    {
        $this->integerMemory[$name] = $atom;
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function getIntegerMemory(string $name): ?int
    {
        if (array_key_exists($name, $this->integerMemory)) {
            return $this->integerMemory[$name]->get();
        }
        return null;
    }

    function setIntegerMemory(string $name, $value, $when = null)
    {
        if (array_key_exists($name, $this->integerMemory))
            if ($when) {
                $this->integerMemory[$name]->cmpset($when, (int)$value);
            } else {
                $this->integerMemory[$name]->set((int)$value);
            }
    }

}