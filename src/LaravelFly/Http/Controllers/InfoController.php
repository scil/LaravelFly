<?php

namespace LaravelFly\Http\Controllers;

class InfoController extends BaseController
{
    /**
     * @var \LaravelFly\Map\Application | \LaravelFly\Simple\Application
     */
    var $app;
    var $server;
    var $swoole;

    function __construct()
    {
        $this->app = app();
        $this->server = $this->app->getServer();
        $this->swoole = $this->server->getSwooleServer();
    }

    function index()
    {
        return [
            'server' => $this->serverInfo(),
            'laravel event listeners cache(in current worker process)' => $this->eventListenersCacheInfo(),
        ];
    }

    function serverInfo()
    {

        $swoole = $this->swoole;


        $info = $swoole->setting + [
                'PID' => $swoole->master_pid,
                'current worker pid' => $swoole->worker_pid,
                'current worker id' => $swoole->worker_id,

            ];

        return $info;
    }

    function eventListenersCacheInfo()
    {

        $methodR = new \ReflectionMethod('LaravelFly\Map\IlluminateBase\Dispatcher', 'getListeners');
        $statics = $methodR->getStaticVariables();

        $r['cached used times'] = $statics['used'];

        list($cached, $cachedEmpty) = $this->_getEventsListenersInfo($statics['cache']);
        $cached['events with no listeners'] = $cachedEmpty;
        $r['cached'] = $cached;

        $eventR = new \ReflectionClass('LaravelFly\Map\IlluminateBase\Dispatcher');
        $d = $eventR->getStaticProperties()['corDict'];
        $allL = $d[\Swoole\Coroutine::getuid()]['listeners'];
        $allWildcards = $d[\Swoole\Coroutine::getuid()]['wildcards'];


        $r['all without empty in current request'] = $this->_getEventsListenersInfo($allL)[0];
        $r['wildcards in current request'] = $this->_getEventsListenersInfo($allWildcards)[0];

        return $r;
    }

    protected function _getEventsListenersInfo($listenersByE)
    {
        $empty = [];
        $has = [];

        foreach ($listenersByE as $eventName => $listeners) {
            if ($listeners) {
                $has[$eventName] = $this->_getListenersInfo($listeners);

            } else {
                $empty[] = $eventName;
            }
        }
        return [$has, $empty];

    }

    protected function _getListenersInfo($listeners)
    {
        foreach ($listeners as $listener) {
            $r = new \ReflectionFunction($listener);
            $bound = $r->getStaticVariables();

            if (is_callable($bound['listener'])) {

                $r2 = new \ReflectionFunction($bound['listener']);

                $current[] = [
                    'this' => get_class($r2->getClosureThis()),
                    'file' => $r2->getFileName(),
                    'wildcard' => $bound['wildcard'],
                ];
            } else {
                $current[] = [
                    'class' => $bound['listener'],
                    'wildcard' => $bound['wildcard'],
                ];

            }
        }
        return $current;

    }

}