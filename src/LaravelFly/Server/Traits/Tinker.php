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
            echo '[NOTICE] daemonize disabled to allow tinker run normally', PHP_EOL;
        }

        if ($options['worker_num'] == 1) {
            echo '[NOTICE] worker_num is 1, the server can not response any other requests when using tinker', PHP_EOL;
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

        $this->dispatcher->addListener('app.created', function (GenericEvent $event) {
            $event['app']->instance('tinker', \LaravelFly\Tinker\Shell::$instance);
        });

    }


}

