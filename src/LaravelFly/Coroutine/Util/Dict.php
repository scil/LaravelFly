<?php

namespace LaravelFly\Coroutine\Util;


use Illuminate\Container\Container;

trait Dict
{

    // protected static $normalAttriForObj=[];
    // protected static $arrayAttriForObj=[];
    protected static $corDict = [];

    public function initOnWorker($listen = true)
    {
        if (static::$arrayAttriForObj ?? false) {
            foreach (static::$arrayAttriForObj as $attri) {
                static::$corDict[WORKER_COROUTINE_ID][$attri] = [];
            }
        }
        if (static::$normalAttriForObj ?? false) {
            foreach (static::$normalAttriForObj as $attri => $defaultValue) {
                if (is_callable($defaultValue)) {
                    static::$corDict[WORKER_COROUTINE_ID][$attri] = $defaultValue();
                } else {
                    static::$corDict[WORKER_COROUTINE_ID][$attri] = $defaultValue;
                }
            }
        }

        if ($listen) {
            Container::getInstance()->make('events')->listen('cor.start', function ($cid) {
                $this->initForCorontine($cid);
            });

            Container::getInstance()->make('events')->listen('cor.end', function ($cid) {
                $this->delForCoroutine($cid);
            });
        }


    }

    public function initForCorontine($cid)
    {
        static::$corDict[$cid] = static::$corDict[WORKER_COROUTINE_ID];
    }

    function delForCoroutine(int $cid)
    {
        unset(static::$corDict[$cid]);
    }


}