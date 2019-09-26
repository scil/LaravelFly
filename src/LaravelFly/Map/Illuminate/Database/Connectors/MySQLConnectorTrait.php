<?php

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;

trait MySQLConnectorTrait
{

    /**
     * @param array $config
     * @return SwoolePDO
     */
    public function connectSwoolePDO(array $config)
    {
        $connection = new SwoolePDO();
        $r = $connection->connect([
            'host'        => Arr::get($config, 'host', '127.0.0.1'),
            'port'        => Arr::get($config, 'port', 3306),
            'user'        => Arr::get($config, 'username', 'hhxsv5'),
            'password'    => Arr::get($config, 'password', '52100'),
            'database'    => Arr::get($config, 'database', 'test'),
            'timeout'     => Arr::get($config, 'timeout', 5),
            'charset'     => Arr::get($config, 'charset', 'utf8mb4'),
            'strict_type' => Arr::get($config, 'strict', false),
        ]);


        if (isset($config['timezone'])) {
            $connection->query('set time_zone="' . $config['timezone'] . '"');
        }

        if (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->query("set session sql_mode='STRICT_ALL_TABLES,ANSI_QUOTES'");
            } else {
                $connection->query("set session sql_mode='ANSI_QUOTES'");
            }
        }
        
        
        return $connection;
    }


}