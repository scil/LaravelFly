<?php

namespace LaravelFly\Map\IlluminateBase;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\HeaderBag;

class Request extends \Illuminate\Http\Request
{

    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {

    }

    /**
     * Create an Illuminate request from a swoole request
     *
     * from: Illuminate\Http\Request::createFromBase
     */
    public function createFromSwoole(\swoole_http_request $request)
    {
        $server = [];

        foreach ($request->server as $key => $value) {
            $server[strtoupper($key)] = $value;
        }

        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$_key] = $value;
        }


        $this->initialize(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawContent() ?: null
        );

        $request->request = $this->getInputSource();
        return $this;
    }


}