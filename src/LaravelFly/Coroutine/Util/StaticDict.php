<?php

namespace LaravelFly\Coroutine\Util;


use Illuminate\Container\Container;

trait StaticDict
{

    /**
     * @var array
     *
     * protected static $normalStaticAttri=[];
     * protected static $arrayStaticAttri=[];
     *
     */
    protected static $corStaticDict = [];


    static public function initStaticForCorontine($cid, $listen = true)
    {
        if ($cid > 0) {
            static::$corStaticDict[$cid] = static::$corStaticDict[WORKER_COROUTINE_ID];
        } else {
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
                Container::getInstance()->make('events')->listen('cor.start', function ($cid) {
                    static::initStaticForCorontine($cid);
                });

                Container::getInstance()->make('events')->listen('cor.end', function ($cid) {
                    static::delStaticForCoroutine($cid);
                });
            }

        }
    }

    static function delStaticForCoroutine(int $cid)
    {
        unset(static::$corStaticDict[$cid]);
    }


}