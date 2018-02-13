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
        return parent::getCasters();
    }
}