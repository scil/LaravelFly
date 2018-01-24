<?php
/**
 *
 *
 * Date: 2018/1/20
 * Time: 22:39
 */

namespace LaravelFly\Coroutine\Illuminate\Database\Connectors;


use Exception;

class MySqlConnector extends \Illuminate\Database\Connectors\MySqlConnector
{

    public function connect(array $config)
    {

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createCoroutineMySQLConnection($config, $options);

        if (! empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        //todo
//        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        //todo
//        $this->setModes($connection, $config);

        return $connection;
    }
    protected function createCoroutineMySQLConnection( array $config, array $options)
    {

        try {
            $config['user']=$config['username'];
            $config['strict_type']=$config['strict'];
            return new \LaravelFly\Coroutine\Illuminate\Database\FakePDO\SwooleCoroutineMySQL($config);
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e, null, $config['username'], $config['password'], $options
            );
        }
    }
}