<?php

namespace LaravelFly\Map\Util;


use Illuminate\Container\Container;

trait StaticDict
{

    /**
     * @var array
     *
     // protected static $normalStaticAttri=[];
     // protected static $arrayStaticAttri=[];
     *
     */
    protected static $corStaticDict = [];


    static public function initStaticForCorontine($cid, $listen = true)
    {
        if (static::$arrayStaticAttri ?? false) {
            foreach (static::$arrayStaticAttri as $attri) {
                static::$corStaticDict[WORKER_COROUTINE_ID][$attri] = [];
            }
        }
        if (static::$normalStaticAttri ?? false) {
            foreach (static::$normalStaticAttri as $attri => $defaultValue) {
                if (is_callable($defaultValue)) {
                    static::$corStaticDict[WORKER_COROUTINE_ID][$attri] = $defaultValue();
                } else {
                    static::$corStaticDict[WORKER_COROUTINE_ID][$attri] = $defaultValue;
                }
            }
        }

        if ($listen) {
            Container::getInstance()->make('events')->listen('request.corinit', function ($cid) {
                static::initStaticForRequestCorontine($cid);
            });

            Container::getInstance()->make('events')->listen('request.corunset', function ($cid) {
                static::unsetStaticForRequestCorontine($cid);
            });
        }


    }

    static protected function initStaticForRequestCorontine($cid)
    {
        static::$corStaticDict[$cid] = static::$corStaticDict[WORKER_COROUTINE_ID];
    }

    static protected function unsetStaticForRequestCorontine(int $cid)
    {
        unset(static::$corStaticDict[$cid]);
    }


}