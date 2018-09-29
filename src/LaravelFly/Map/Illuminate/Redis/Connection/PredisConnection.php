<?php
/**
 * User: scil
 * Date: 2018/9/29
 * Time: 11:25
 */


namespace LaravelFly\Map\Illuminate\Redis\Connection;

class PredisConnection extends \Illuminate\Redis\Connections\PredisConnection implements EnsureConnected
{
    public function ensureConnected(){
       if(!$this->client->isConnected()){
           $this->client->connect();
       }
           ;
    }

}