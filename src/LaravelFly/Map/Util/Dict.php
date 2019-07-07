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
            $event = Container::getInstance()->make('events');

            $event->listen('request.corinit', function ($cid) {
                $this->initForRequestCorontine($cid);
            });

            $event->listen('request.corunset', function ($cid) {
                $this->unsetForRequestCorontine($cid);
            });


            $event->listen('usercor.init', function ($parentId, $childId) {
                $this->initUserCoroutine($parentId, $childId);
            });

            $event->listen('usercor.unset', function ($childId) {
                $this->unsetUserCoroutine($childId);

            });
            $event->listen('usercor.unset2', function ($parentId, $childId) {
                $this->unsetUserCoroutine2($parentId, $childId);

            });
        }


    }

    function initForRequestCorontine($cid)
    {
        static::$corDict[$cid] = static::$corDict[WORKER_COROUTINE_ID];

//        $f = fopen('/vagrant/www/zc/tmp', 'a+');
//        fwrite($f, "\ninited ". get_class($this) ."\n ". implode(',', array_keys(static::$corDict)) );
//        fclose($f);
    }

    function unsetForRequestCorontine(int $cid)
    {
        unset(static::$corDict[$cid]);
    }

    function initUserCoroutine($parentId, $childId)
    {
        static::$corDict[$childId] = static::$corDict[$parentId];
    }

    function unsetUserCoroutine($childId)
    {

        unset(static::$corDict[$childId]);
    }

    function unsetUserCoroutine2($parentId, $childId)
    {
        /**
         *
         * low risk?
         * Does \Co::getUid() has the possibility to return same number in a worker process? #1977
         * https://github.com/swoole/swoole-src/issues/1977#issuecomment-422232642
         */
        if (isset(static::$corDict[$parentId]))
            static::$corDict[$parentId] = static::$corDict[$childId];

        unset(static::$corDict[$childId]);
    }

}