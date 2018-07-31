<?php

namespace LaravelFly\Server\Traits;

use Symfony\Component\EventDispatcher\GenericEvent;

Trait Tinker
{

    protected function prepareTinker(&$options)
    {

        if (empty($options['tinker'])) return;

        if ($options['daemonize'] == true) {
            $options['daemonize'] = false;
            $this->echo(
                "daemonize disabled to allow tinker run normally",
                'WARNING',true
            );
        }

        if ($options['worker_num'] == 1) {
            $this->echo(
                "worker_num is 1, the server can not response any other requests when using tinker",
                'WARN',true
            );
        }

        $this->tinkerSubscriber();

    }

    protected function tinkerSubscriber()
    {

        $this->dispatcher->addListener('worker.starting', function (GenericEvent $event) {
            \LaravelFly\Tinker\Shell::make($event['server']);

            \LaravelFly\Tinker\Shell::addAlias([
                \LaravelFly\Fly::class,
            ]);
        });

        $this->dispatcher->addListener('laravel.ready', function (GenericEvent $event) {
            $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
        });

    }


}

