<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 10:01
 */

namespace LaravelFly\Map\Illuminate\Database\Pool;

use LaravelFly\Map\Illuminate\Database\DatabaseManager;
use LaravelFly\Map\Illuminate\Redis\RedisManager;

trait Pool
{

    /**
     * @var DatabaseManager|RedisManager
     */
    protected $dbmgr;

    /**
     * @var string $name
     */
    protected $name;

    function initPool($name, $db, $dispatcher)
    {

        assert(method_exists($db, 'makeConnectionForPool'));

        $this->name = $name;

        /**
         * @var DatabaseManager| RedisManager $db
         */
        $this->dbmgr = $db;

    }
    abstract function borrow();
    abstract function return($conn);
    abstract function createConnection();

}