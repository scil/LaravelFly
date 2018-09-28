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

    protected function _getDictVars($obj, array $props, $cid = null): array
    {

        $instanceR = new \ReflectionClass($obj);
        $d = $instanceR->getStaticProperties()['corDict'];

        foreach ($props as $prop) {
            $r[] = $d[$cid ?: \Swoole\Coroutine::getuid()][$prop];
        }
        return $r;

    }

    protected function _getDictVarsOnWorker($obj, array $props): array
    {
        return $this->_getDictVars($obj, $props, WORKER_COROUTINE_ID);

    }

    function index($sub = null)
    {
        if ($sub && method_exists($this, $sub . 'Info')) {

            $info = $this->{$sub . 'Info'}();

            if (\View::exists("laravel-fly::info.$sub"))
                $view = "laravel-fly::info.$sub";
            elseif (!empty($info[0]) && !empty($info[0]['caption']))
                // for multiple tables
                $view = 'laravel-fly::info.table-table';
            else
                $view = 'laravel-fly::info.table';


            return view($view, [
                'caption' => $sub,
                'info' => $info,
            ]);
        }
//        return $this->serverInfo();
        return view('laravel-fly::info.index', [
            'server' => $this->serverInfo(),
            'header' => $this->headerInfo(),
        ]);
    }

    function dbInfo()
    {
        $tables = [];

        $defaultPoolsize = \LaravelFly\Fly::getServer()->getConfig('poolsize');

        // connection info
        foreach (config('database.connections') as $name => $config) {

            $size =  $config['poolsize'] ?? $defaultPoolsize;
            
            $driver = $config['driver'];
            list($info, $columns) = $this->_getConnectionInfo($name, $driver, $config);

            $tables[] = [

                'caption' =>  "connections of $driver (poolsize: $size)",
                'table_type' => 'grid',
                'columns' => $columns,
                'data' => $info,

            ];
        }

        return $tables;

    }

    protected function _getConnectionInfo($name, $drive, $config)
    {
        switch ($drive) {
            case 'mysql':
                return [
                    \DB::connection($name)->select('show processlist'),
                    ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info']
                ];
            case 'pgsql':
                $database = $config['database'];

                // https://serverfault.com/questions/128284/how-to-see-active-connections-and-current-activity-in-postgresql-8-4

                $columns = ['pid', 'datname', 'usename', 'application_name', 'client_hostname', 'client_port',
                    'backend_start', 'query_start', 'query'];

                $selectColumns = implode(',', $columns);

                $q = "select  $selectColumns from pg_stat_activity WHERE state <> 'idle' AND pid<>pg_backend_pid() ";

                return [
                    \DB::connection($name)->select($q),
                    $columns
                ];
            case 'sqlite':
                $file = $config['database'];
                if (!is_file($file)) break;
                $cmd = "lsof $file";
                // on a POSIX type system you can use the lsof
                // https://stackoverflow.com/questions/12138260/how-can-i-count-the-number-of-open-connections-in-an-sqlite-database
                exec($cmd, $r, $succ);
                if ($succ != 0) break;

                $head = array_shift($r);
                $head = preg_split('/\s+/', $head);
                $data = [];
                foreach ($r as $line) {
                    $data[] = preg_split('/\s+/', $line);
                }
                return [$data, $head];


        }

        return [[], []];
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

    function routesWorkerInfo()
    {
        if (LARAVELFLY_SERVICES['routes']) {
            return [
                [
                    'caption' => "
app('routes') alway ref to the same object, 
<br>'routesWorker' same as 'routes', 
<br>because LARAVELFLY_SERVICES['routes'] === true",
                    'data' => [],
                ]
            ];
        }

        $obj = $this->_getWorkerService('routes');

        if (!$obj) return [
            ['caption' => 'no data', 'data' => [],]
        ];

        return $this->_getProtectVars($obj,
            ['routes', 'allRoutes', 'nameList', 'actionList'], WORKER_COROUTINE_ID);
    }

    protected
    function _getWorkerService($name = null)
    {
        list($instances) = $this->_getDictVarsOnWorker(app(), ['instances']);

        if ($name) return isset($instances[$name]) ? $instances[$name] : null;

        return $instances;

    }

    protected
    function _getProtectVars(object $instance, array $props): array
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
        $tables[] = [
            'caption' => 'cached',
            'data' => $cached,
        ];

        list($allCurrent, $allWildcardsCurrent) =
            $this->_getDictVars('LaravelFly\Map\IlluminateBase\Dispatcher', ['listeners', 'wildcards']);


        $tables[] = [
            'caption' => 'without wildcard in current request',
            'data' => $this->_getEventsListenersInfo($allCurrent)[0],
        ];

        $tables[] = [
            'caption' => 'with wildcard in current request',
            'data' => $this->_getEventsListenersInfo($allWildcardsCurrent)[0],
        ];

        return $tables;
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

    protected
    function _getListenersInfo($listeners)
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

                    if (strpos($v, '<br>') !== false) {
                        $item[] = $padding . "$key:<br>" . $v;
                    } else
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