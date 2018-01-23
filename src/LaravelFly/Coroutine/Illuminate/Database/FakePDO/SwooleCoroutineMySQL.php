<?php

namespace LaravelFly\Coroutine\Illuminate\Database\FakePDO;

/**
 * a layer between swoole coroutine mysql and PDO
 * using PDO API
 *
 * @package LaravelFly\Coroutine\Illuminate\Database
 */
class SwooleCoroutineMySQL
{
    protected $swoole;

    function __construct($config)
    {
        $config['user']=$config['user']??$config['username'];
        $this->swoole = new \Swoole\Coroutine\MySQL();
        $this->swoole->connect($config);
        return $this;
    }
    function prepare($query){
        echo 'swoole prepare ', $query,PHP_EOL ;
        $r= $this->swoole->prepare($query);
        var_dump($r);
        die('now prepare return bool true, waiting swoole new version 2.0.13');
        return new SwooleCoroutineMySQLStatement($r);
    }
    function exec($query){
        echo 'swoole exec',PHP_EOL;
        var_dump($query);
        return $this->swoole->query($query);
    }
}

class SwooleCoroutineMySQLStatement{
    var $binded=[];
    var $stmt;
    function __construct(\Swoole\Coroutine\MySQL\Statement $statement)
    {
        $this->stmt=$statement;
    }

    function bindValue($index,$value){
       $this->binded[]=$value;
   }
   function execute(){
        return $this->stmt->execute($this->binded);

   }
}