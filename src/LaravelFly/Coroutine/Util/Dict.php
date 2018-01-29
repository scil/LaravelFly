<?php

namespace LaravelFly\Coroutine\Util;


use Illuminate\Container\Container;

trait Dict
{

    protected $corDict = [];

    public function initForCorontine($cid, $listen=false)
    {
        if ($cid > 0) {
            $this->corDict[$cid] = $this->corDict[-1];
        } else {
            if ($this->arrayAttriForObj) {
                foreach ($this->arrayAttriForObj as $attri) {
                    $this->corDict[-1][$attri] = [];
                }
            }
            if ($this->normalAttriForObj) {
                foreach ($this->normalAttriForObj as $attri => $defaultValue) {
                    if (is_callable($defaultValue)) {
                        $this->corDict[-1][$attri] = $defaultValue();
                    } else {
                        $this->corDict[-1][$attri] = $defaultValue;
                    }
                }
            }

            if($listen){
                Container::getInstance()->make('events')->listen('cor.start',function ($cid){
                    $this->initForCorontine($cid);
                });

                Container::getInstance()->make('events')->listen('cor.end',function ($cid){
                    $this->delForCoroutine($cid);
                });
            }

        }
    }

    function delForCoroutine(int $cid)
    {
        unset($this->corDict[$cid]);
    }


}