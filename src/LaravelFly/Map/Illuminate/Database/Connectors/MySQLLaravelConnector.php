<?php

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Illuminate\Support\Arr;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Support\Str;

use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;


class MySQLLaravelConnector extends Connector implements ConnectorInterface
{
    use MySQLConnectorTrait;

    /**
     * @param string $dsn
     * @param array $config
     * @param array $options
     * @return SwoolePDO
     * @throws \Throwable
     */
    public function createConnection($dsn, array $config, array $options)
    {
        try {
            $mysql = $this->connect($config);
        } catch (\Exception $e) {
            $mysql = $this->tryAgainIfCausedByLostConnectionForCoroutineMySQL($e, $config);
        }

        return $mysql;
    }

    public function connect(array $config)
    {
        return $this->connectSwoolePDO($config);

    }
    /**
     * @param \Throwable $e
     * @param array $config
     * @return SwoolePDO
     * @throws \Throwable
     */
    protected function tryAgainIfCausedByLostConnectionForCoroutineMySQL($e, array $config)
    {
        if (parent::causedByLostConnection($e) || Str::contains($e->getMessage(), ['is closed', 'is not established'])) {
            return $this->connect($config);
        }
        throw $e;
    }



}