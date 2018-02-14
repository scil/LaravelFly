<?php
/**
 * User: scil
 * Date: 2018/2/13
 * Time: 17:13
 */

namespace LaravelFly\Tinker;


class TinkerCommand extends \Laravel\Tinker\Console\TinkerCommand
{
    public function getCasters()
    {
        $casters = parent::getCasters();

        if (class_exists('Swoole\Server\Port')) {
            $casters['Swoole\Server\Port'] = 'LaravelFly\Tinker\TinkerCaster::castSwooleServerPort';
        }
        if (class_exists('Swoole\Http\Server')) {
            $casters['Swoole\Http\Server'] = 'LaravelFly\Tinker\TinkerCaster::castSwooleHttpServer';
        }

        return $casters;

    }

}