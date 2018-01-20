<?php

namespace LaravelFly\Coroutine\Util;


trait Dict
{

    protected $corDict=[];

    public function initForCorontine($cid)
    {
        if($cid>0){
            $this->corDict[$cid]=$this->corDict[-1];
        }else{
            if ($this->arrayAttriForObj) {
                foreach ($this->arrayAttriForObj as $attri) {
                    $this->corDict[-1][$attri] = [] ;
                }
            }
            if ($this->normalAttriForObj) {
                foreach ($this->normalAttriForObj as $attri => $defaultValue) {
                    if(is_callable($defaultValue)){
                        $this->corDict[-1][$attri] =  $defaultValue() ;
                    }else{
                        $this->corDict[-1][$attri] =  $defaultValue ;
                    }
                }

            }
        }
    }

    function delForCoroutine(int $cid)
    {
        unset($this->corDict[$cid]);
    }


}