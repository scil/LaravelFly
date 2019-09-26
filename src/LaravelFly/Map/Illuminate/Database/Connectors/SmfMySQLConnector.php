<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 23:13
 */

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Smf\ConnectionPool\Connectors\CoroutineMySQLConnector;

class SmfMySQLConnector extends  CoroutineMySQLConnector
{
    use MySQLConnectorTrait;

    public function connect(array $config)
    {
        return $this->connectSwoolePDO($config);

    }
}