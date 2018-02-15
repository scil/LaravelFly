<?php

namespace LaravelFly\Tinker;

use Exception;
use Symfony\Component\VarDumper\Caster\Caster;

class TinkerCaster
{
    /**
     * Application methods to include in the presenter.
     *
     * @var array
     */
    private static $swooleServerPortProperties = [
//        'host',
//        'port',
//        'type',
        'sock',
//        'setting',
    ];

    private static $swooleServerProperties = [
        'worker_id',
        'worker_pid',
        'master_pid',
        'manager_pid',
        'taskworker',
        'connections',
//        'host',
//        'port',
        'type',
        'mode',
        'ports',
        'setting',
        'onConnect',
        'onReceive',
        'onClose',
        'onPacket',
        'onBufferFull',
        'onBufferEmpty',
        'onStart',
        'onShutdown',
        'onWorkerStart',
        'onWorkerStop',
        'onWorkerExit',
        'onWorkerError',
        'onTask',
        'onFinish',
        'onManagerStart',
        'onManagerStop',
        'onPipeMessage',
        'onRequest',
        'onHandshake',
    ];

    /**
     * Get an array representing the properties of a \Swoole\Http\Server
     *
     * @param  \Swoole\Http\Server
     * @return array
     */
    public static function castSwooleHttpServer($server)
    {
        $results = [];

        if($server->setting['worker_num']>1){
            $results[Caster::PREFIX_VIRTUAL . '** TIP **'] = 'worker_id and worker_pid are of only the worker in which tinker() running';
        }

        foreach (self::$swooleServerProperties as $prop) {
            $v = $server->$prop;

            if (is_null($v)) continue;

            $results[Caster::PREFIX_VIRTUAL . $prop] = $v;
        }

        return $results;

    }

    /**
     * Get an array representing the properties of a \Swoole\Server\Port.
     *
     * @param  \Swoole\Server\Port
     * @return array
     */
    public static function castSwooleServerPort($port)
    {
        $results = [];

        foreach (self::$swooleServerPortProperties as $property) {
            try {
                $val = $port->$property;

                if (!is_null($val)) {
                    $results[Caster::PREFIX_VIRTUAL . $property] = $val;
                }
            } catch (Exception $e) {
                //
            }
        }

        return $results;
    }
}

