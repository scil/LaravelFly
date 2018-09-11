<?php

namespace LaravelFly\FrontEnd\Controllers;

class InfoController extends BaseController
{
    /**
     * @var \LaravelFly\Map\Application | \LaravelFly\Backup\Application
     */
    var $app;
    var $server;
    var $swoole;

    function __construct()
    {
        $this->app = app();
        $this->server = $this->app->getServer();
        $swoole = $this->swoole = $this->server->getSwooleServer();

        \View::share('worker_pid', $swoole->worker_pid);
        \View::share('worker_id', $swoole->worker_id);

    }

    protected function _getDictVars($class, array $props): array
    {
        $eventR = new \ReflectionClass($class);
        $d = $eventR->getStaticProperties()['corDict'];

        foreach ($props as $prop) {
            $r[] = $d[\Swoole\Coroutine::getuid()][$prop];
        }
        return $r;

    }

    function index($sub = null)
    {
        if ($sub && method_exists($this, $sub . 'Info')) {

            $data = $this->{$sub . 'Info'}();

            if (\View::exists("laravel-fly::info.$sub"))
                $view = "laravel-fly::info.$sub";
            elseif (!empty($data[0]) && !empty($data[0]['caption']))
                $view = 'laravel-fly::info.table-table';
            else
                $view = 'laravel-fly::info.table';


            return view($view, [
                'caption' => $sub,
                'data' => $data,
            ]);
        }
//        return $this->serverInfo();
        return view('laravel-fly::info.index', [
            'server' => $this->serverInfo(),
            'header' => $this->headerInfo(),
        ]);
    }

    function headerInfo()
    {
        return \Request::header();
    }

    function serverInfo()
    {

        $swoole = $this->swoole;


        $info = [
                'master pid' => $swoole->master_pid,
                'current worker pid' => $swoole->worker_pid,
                'current worker id' => $swoole->worker_id,
            ] + $swoole->setting;

        return $info;
    }

    function routesInfo()
    {
        return $this->_getProtectVars(app('routes'), ['routes', 'allRoutes', 'nameList', 'actionList']);
    }

    protected function _getProtectVars(object $instance, array $props): array
    {
        $reflec = new \ReflectionClass($instance);

        foreach ($props as $prop) {
            $rProp = $reflec->getProperty($prop);
            $rProp->setAccessible(true);
            $r[] = [
                'caption' => $prop,
                'data' => $rProp->getValue($instance)
            ];
        }

        return $r;

    }

    function eventListenersInfo()
    {

        $methodR = new \ReflectionMethod('LaravelFly\Map\IlluminateBase\Dispatcher', 'getListeners');
        $statics = $methodR->getStaticVariables();


        list($cached, $cachedEmpty) = $this->_getEventsListenersInfo($statics['listenersCache']);
        $cached['events with no listeners'] = $cachedEmpty;
        $cached = [
                '(cached used times)' =>
                    $statics['used'] . ' (only used when there\' s no handlers with wildcard registerd in a request )'
            ]
            + $cached;

        // $result['cached'] = $cached;
        $result[] = [
            'caption' => 'cached',
            'data' => $cached,
        ];

        list($allCurrent, $allWildcardsCurrent) =
            $this->_getDictVars('LaravelFly\Map\IlluminateBase\Dispatcher', ['listeners', 'wildcards']);


        $result[] = [
            'caption' => 'without wildcard in current request',
            'data' => $this->_getEventsListenersInfo($allCurrent)[0],
        ];

        $result[] = [
            'caption' => 'with wildcard in current request',
            'data' => $this->_getEventsListenersInfo($allWildcardsCurrent)[0],
        ];

        return $result;
    }

    protected
    function _getEventsListenersInfo($listenersByEventName)
    {
        $empty = [];
        $has = [];

        foreach ($listenersByEventName as $eventName => $listeners) {
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


            if (is_array($bound['listener'])) {
                $r2 = new \ReflectionMethod($bound['listener'][0], $bound['listener'][1]);

                $current[] = [
                    'class' => get_class($bound['listener'][0]),
                    'method' => $bound['listener'][1],
                    'file' => $r2->getFileName(),
                    'wildcard' => $bound['wildcard'],
                ];
            } elseif (is_callable($bound['listener'])) {
                $r2 = new \ReflectionFunction($bound['listener']);

                $current[] = [
                    'this' => (null !== $r2->getClosureThis()) ? get_class($r2->getClosureThis()) : null,
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


    static function renderValue($value, $deep = 0)
    {
        $padding = str_repeat('&nbsp;', $deep);

        if (is_object($value)) {

            if ($value instanceof \Illuminate\Routing\Route) {

                return static::renderValue([
                    'uri' => $value->uri(),
                    'action' => $value->getActionName(),
                ], $deep + 1);
            }
        }

        if (is_array($value)) {

            if (count($value) === 1 && !empty($value[0]))
                return $padding . static::renderValue($value[0], $deep + 1);
            else {
                $item = [];
                foreach ($value as $key => $v) {
                    $v = static::renderValue($v, $deep + 1);

                    if (strpos($v,'<br>') !== false){
                        $item[] = $padding . "$key:<br>" . $v;
                    }
                    else
                        $item[] = $padding . "$key: " . $v;
                }
                return implode("<br>", $item) . str_repeat('<br>', 1);
            }

        }

        if (is_callable($value)) {
            return '(callable)';
        }

        if (false === $value)
            return 'false';

        return $value;
    }

}