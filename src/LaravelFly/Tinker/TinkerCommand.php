<?php
/**
 * User: scil
 * Date: 2018/2/13
 * Time: 17:13
 */

namespace LaravelFly\Tinker;


class TinkerCommand extends \Laravel\Tinker\Console\TinkerCommand
{
    var $application;

    public function hasApplication()
    {
        return $this->application;
    }
    public function getCasters()
    {
        return parent::getCasters();
    }
    public function getCommands()
    {
        return parent::getCommands();
    }
}