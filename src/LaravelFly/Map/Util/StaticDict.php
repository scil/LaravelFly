<?php

namespace LaravelFly\Map\Util;


use Illuminate\Container\Container;

trait StaticDict
{

    // protected static $normalStaticAttri = [];
    // protected static $arrayStaticAttri = [];

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
            $event = Container::getInstance()->make('events');

            $event->listen('request.corinit', function ($cid) {
                static::initStaticForRequestCorontine($cid);
            });

            $event->listen('request.corunset', function ($cid) {
                static::unsetStaticForRequestCorontine($cid);
            });

            $event->listen('usercor.init', function ($parentId, $childId) {
                static::initStaticUserCoroutine($parentId, $childId);
            });

            $event->listen('usercor.unset', function ($childId) {
                static::unsetStaticUserCoroutine($childId);
            });
            $event->listen('usercor.unset2', function ($parentId, $childId, $write) {
                static::unsetStaticUserCoroutine2($parentId, $childId, $write);
            });

        }


    }

    static function initStaticForRequestCorontine($cid)
    {
        static::$corStaticDict[$cid] = static::$corStaticDict[WORKER_COROUTINE_ID];
    }

    static function unsetStaticForRequestCorontine(int $cid)
    {
        unset(static::$corStaticDict[$cid]);
    }

    static function initStaticUserCoroutine($parentId, $childId)
    {
        static::$corStaticDict[$childId] = static::$corStaticDict[$parentId];
    }

    static function unsetStaticUserCoroutine($childId)
    {
        unset(static::$corStaticDict[$childId]);
    }

    static function unsetStaticUserCoroutine2($parentId, $childId, $write)
    {
        if ($write && isset(static::$corStaticDict[$parentId]))
            static::$corStaticDict[$parentId] = static::$corStaticDict[$childId];

        unset(static::$corStaticDict[$childId]);
    }

}