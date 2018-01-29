<?php

namespace LaravelFly\Coroutine\Util;


use Illuminate\Container\Container;

trait Dict
{

    // protected $normalAttriForObj=[];
    // protected $arrayAttriForObj=[];
    protected $corDict = [];

    public function initOnWorker($listen = true)
    {
        if ($this->arrayAttriForObj ?? false) {
            foreach ($this->arrayAttriForObj as $attri) {
                $this->corDict[-1][$attri] = [];
            }
        }
        if ($this->normalAttriForObj ?? false) {
            foreach ($this->normalAttriForObj as $attri => $defaultValue) {
                if (is_callable($defaultValue)) {
                    $this->corDict[-1][$attri] = $defaultValue();
                } else {
                    $this->corDict[-1][$attri] = $defaultValue;
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
        $this->corDict[$cid] = $this->corDict[-1];
    }

    function delForCoroutine(int $cid)
    {
        unset($this->corDict[$cid]);
    }


}