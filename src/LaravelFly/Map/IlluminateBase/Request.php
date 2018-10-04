<?php

namespace LaravelFly\Map\IlluminateBase;


use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ServerBag;

class Request extends \Illuminate\Http\Request
{
    use \LaravelFly\Map\Util\Dict;

    protected static $normalAttriForObj = [
        // symfony

        'content' => null,

        'requestUri' => null, 'baseUrl' => null, 'pathInfo' => null, 'basePath' => null, 'method' => null, 'format' => null,

        'isHostValid' => null, 'isForwardedValid' => null,

        // an object
        'session' => null,

        // server objects
        'attributes' => null, 'request' => null, 'query' => null, 'files' => null, 'cookies' => null, 'headers' => null, 'server' => null,

        // ★★★ their are arrays, but treated as normal value
        'languages' => null, 'charsets' => null, 'encodings' => null, 'acceptableContentTypes' => null,

        // laravel
        'json' => null,
        'userResolver' => null,
        'routeResolver' => null,
        // ★★★ their are arrays, but treated as normal value
        'convertedFiles' => null,

        // cache vars prefix with fly
        'flyRoot' => null, 'flyUrl' => null, 'flyFullUrl' => null, 'flyUrlWithQuery' => null, 'flyPath' => null, 'flySegments' => null, 'flyIp' => null, 'flyIps' => null, 'flyInputSource' => null,
        'flyAll'=>null, 'flyChangedForAll' => true,

    ];

    protected static $arrayAttriForObj = [
        // treat them as normalAttri, althout their values are arrays.
        // 'languages', 'charsets', 'encodings','acceptableContentTypes',

        //laravel
        // 'convertedFiles',
    ];

    protected static $instance;

    function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        if (static::$instance) throw new SingletonRequestException();

        static::$instance = $this;

        $this->initOnWorker(false);

        // moved to LaravelFly\Server\HttpServer::start
        // static::enableHttpMethodParameterOverride();

    }

    function initForRequestCorontineWithSwoole($cid, \swoole_http_request $swoole_request)
    {
        // no worry about STALE REFERENCE about seven objects, they are null in $corDict[WORKER_COROUTINE_ID]
        static::$corDict[$cid] = static::$corDict[WORKER_COROUTINE_ID];

        $dict = &static::$corDict[$cid];

        $server = [];
        foreach ($swoole_request->server as $key => $value) {
            $server[strtoupper($key)] = $value;
        }
        foreach ($swoole_request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$_key] = $value;
        }

        /**
         * see: duplicate
         */
        // step1: filterFiles
        // todo
        $files = $swoole_request->files ?? [];
        //
        // step2: _format
        // i think not necessary
        //
        // step3: getRequestFormat
        // i think not necessary

        // server objects
        $dict['request'] = new ParameterBag($swoole_request->post ?? []);
        $dict['query'] = new ParameterBag($swoole_request->get ?? []);
        $dict['attributes'] = new ParameterBag([]);
        $dict['cookies'] = new ParameterBag($swoole_request->cookie ?? []);
        $dict['files'] = new FileBag($files);
        $dict['server'] = new ServerBag($server);
        $dict['headers'] = new HeaderBag($dict['server']->getHeaders());


        /**
         * see: ::createFromBase();
         */
        $dict['request'] = $this->getinputsource();

        $dict['content'] = $swoole_request->rawContent() ?: null;
    }

    function unsetForRequestCorontine(int $cid)
    {
        unset(static::$corDict[$cid]);

    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    function __get($key)
    {
        // hack  support prop access like before, like $request->query
        $dict = static::$corDict[\Swoole\Coroutine::getuid()];
        if (isset($dict[$key]))
            return $dict[$key];

        return parent::__get($key);
    }

    /**
     * hack  support prop write like before, like $request->query = []
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        static::$corDict[\Swoole\Coroutine::getuid()][$name] = $value;
    }

    public function __isset($key)
    {
        return !is_null(parent::__get($key));
    }

}