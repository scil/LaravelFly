<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 23:13
 */

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use LaravelFly\Map\Illuminate\Database\Connection\SwooleMySQLConnection;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;
use Smf\ConnectionPool\Connectors\CoroutineMySQLConnector;

class MySQLSmfConnector extends  CoroutineMySQLConnector
{
    use MySQLConnectorTrait;

    public function connect(array $config)
    {
        return $this->connectSwoolePDO($config);

    }
    public function validate($connection): bool
    {
        return $connection instanceof SwooleMySQLConnection;
    }
}