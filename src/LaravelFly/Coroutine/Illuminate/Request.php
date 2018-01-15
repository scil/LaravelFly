<?php

namespace LaravelFly\Coroutine\Illuminate;

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
     * from: Symfony\Component\HttpFoundation\Request::initialize
     */
    public function createFromSwoole(\swoole_http_request $request)
    {

        $this->request = new ParameterBag($request->post ?? []);
        $this->query = new ParameterBag($request->get ?? []);
        $this->attributes = new ParameterBag([]);
        $this->cookies = new ParameterBag($request->cookie ?? []);
        $this->files = new FileBag($request->files ?? []);

        $server = [];
        foreach ($request->server as $key => $value) {
            $server[strtoupper($key)] = $value;
        }
        // todo how to make $this->headers direct
        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$_key] = $value;
        }
        $this->server = new ServerBag($server);

        //todo
//        $this->headers = new HeaderBag($this->convertSwooleRequestHeader($request, $server));
        $this->headers = new HeaderBag($this->server->getHeaders());
//        var_dump($this->headers->all());


        $this->content = $request->rawContent() ?: null;
        $this->languages = null;
        $this->charsets = null;
        $this->encodings = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;

        /**
         * from: Illuminate\Http\Request::createFromBase
         */
        $request->request = $this->getInputSource();
        return $this;
    }

    function convertSwooleRequestHeader($request, $server)
    {
        $headers = [];
        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $headers[$_key] = $value;
        }

        /**
         *  from: Symfony\Component\HttpFoundation\ServerBag::getHeaders
         */
        foreach (array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE') as $key) {
            if (isset($server[$key]))
                $headers[$key] = $server[$key];
        }

        return $this->getHeaders($headers);
    }

    /**
     * from: \Symfony\Component\HttpFoundation\ServerBag\getHeaders
     * which called by  Symfony\Component\HttpFoundation\Request::initialize
     *
     * todo: is this function necessary?
     *
     * @return array
     */
    private function getHeaders($headers)
    {

        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW']) ? $this->parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (2 == count($exploded)) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                } elseif (empty($this->parameters['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $this->parameters['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      http://php.net/manual/en/reserved.variables.server.php
                     */
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }

        if (isset($headers['AUTHORIZATION'])) {
            return $headers;
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }

        return $headers;
    }
}