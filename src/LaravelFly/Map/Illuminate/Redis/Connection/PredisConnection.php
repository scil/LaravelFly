<?php

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