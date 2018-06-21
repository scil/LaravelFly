<?php

namespace LaravelFly\Map\Util;


use Illuminate\Container\Container;

trait Dict
{

    // protected static $normalAttriForObj=[];
    // protected static $arrayAttriForObj=[];
    protected static $corDict = [];

    /**
     * @param bool $listen
     * some services sould be handled in
     * \LaravelFly\Map\Application::initForRequestCorontine and unsetForRequestCorontine, so set $listen = false
     *
     */
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
            Container::getInstance()->make('events')->listen('request.corinit', function ($cid) {
                $this->initForRequestCorontine($cid);
            });

            Container::getInstance()->make('events')->listen('request.corunset', function ($cid) {
                $this->unsetForRequestCorontine($cid);
            });
        }


    }

    public function initForRequestCorontine($cid)
    {
        static::$corDict[$cid] = static::$corDict[WORKER_COROUTINE_ID];
    }

    function unsetForRequestCorontine(int $cid)
    {
        unset(static::$corDict[$cid]);
    }


}