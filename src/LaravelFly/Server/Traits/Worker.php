<?php

namespace LaravelFly\Server\Traits;

use Symfony\Component\EventDispatcher\GenericEvent;
use Storage;

Trait Worker
{
    /**
     * only for echo msg
     * @var bool
     */
    protected $notReady = true;

    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        $this->workerStartHead($server, $worker_id);

        if ($worker_id == 0) {
            $this->workerZeroStartTail($server);
        }

        $this->workerStartTail($server, $worker_id);
    }

    public function workerStartHead(\swoole_server $server, int $worker_id)
    {
        $this->echo("event worker.starting for id $worker_id in pid " . getmypid());

        $this->dispatcher->dispatch('worker.starting',
            new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id]));
    }

    public function workerStartTail(\swoole_server $server, int $worker_id)
    {
        // disable laravel dispatcher, only use server dispatcher
        // event('worker.ready', [$this]);

        // 'app' is null for FpmHttpServer
        $this->dispatcher->dispatch('worker.ready',
            new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id, 'app' => $this->app]));

        $this->echo("event worker.ready for id $worker_id");

    }

    /**
     * do something only in one worker, not in each worker
     *
     * there's alway a worker with id 0.
     * do not worry about if current worker 0 is killed, worker id is in range [0, worker_num)
     *
     * @param \swoole_server $swoole_server
     */
    protected function workerZeroStartTail(\swoole_server $swoole_server)
    {
        if ($this->getConfig('watch_down')) $this->watchDownFile();

        if ($this->getConfig('watch')) $this->watchForHotReload($swoole_server);

        if ($this->notReady) {
            $this->notReady = false;
            echo "\nServer ready for accepting requests!\n";
        }
    }

    protected function watchForHotReload($swoole_server)
    {

        if (!function_exists('inotify_init')) return;

        $this->echo("watch for hot reload.");

        $fd = inotify_init();

        $adapter = Storage::disk('local')->getAdapter();

        // local path prefix is app()->storagePath()
        $oldPathPrefix = $adapter->getPathPrefix();
        $adapter->setPathPrefix('/');

        foreach ($this->getConfig('watch') as $item) {

            if (!file_exists($item)) {
                $this->echo("not exists: $item.", 'WARN');
                continue;
            }

            inotify_add_watch($fd, $item, IN_CREATE | IN_DELETE | IN_MODIFY);

            if (is_dir($item)) {
                foreach (Storage::disk('local')->allDirectories($item) as $cItem) {
                    inotify_add_watch($fd, "/$cItem", IN_CREATE | IN_DELETE | IN_MODIFY);
                }
            }
        }

        $adapter->setPathPrefix($oldPathPrefix);

        $delay = $this->getConfig('watch_delay');

        swoole_event_add($fd, function () use ($fd, $swoole_server, $delay) {
            static $timer = null;

            if (inotify_read($fd)) {

                if ($timer !== null) $swoole_server->clearTimer($timer);

                $timer = $swoole_server->after($delay, function () use ($swoole_server) {
                    $this->echo("hot reload");
                    $swoole_server->reload();
                });

            }
        });


    }

    public function getDownFileDir()
    {
        return $this->path('storage/framework');
    }

    /**
     * use a Atomic vars to save if app is down,
     * allow \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class a little bit faster
     */
    protected function watchDownFile()
    {

        $dir = $this->getDownFileDir();

        $downFile = $dir . '/down';

        $this->echo("watch $downFile");


        if (function_exists('inotify_init')) {

            $fd = inotify_init();
            inotify_add_watch($fd, $dir, IN_CREATE | IN_DELETE);

            swoole_event_add($fd, function () use ($fd, $downFile) {
                $events = inotify_read($fd);
                if ($events && $events[0]['name'] === 'down') {
                    $this->setIntegerMemory('isDown', file_exists($downFile));
                }
            });

        } else {

            swoole_timer_tick(1000, function () use ($downFile) {
                $this->integerMemory['isDown']->set((int)file_exists($downFile));
            });
        }
    }

    public function onWorkerStop(\swoole_server $server, int $worker_id)
    {
        $this->echo("event worker.stopped for id $worker_id, statcache and opcache cleaned later");

        $this->dispatcher->dispatch('worker.stopped',
            new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id, 'app' => $this->app]));

        clearstatcache();
        opcache_reset();
    }

}

