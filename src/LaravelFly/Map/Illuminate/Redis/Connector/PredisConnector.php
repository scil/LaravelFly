<?php
namespace LaravelFly\Map\Illuminate\Redis\Connector;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Redis\Connection\PredisConnection;
use Predis\Client;

class PredisConnector extends \Illuminate\Redis\Connectors\PredisConnector
{

    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );

        // hack
        // disable persistent, we have got connection pool
        $config['persistent'] = false;

        return new PredisConnection(new Client($config, $formattedOptions));
    }
}