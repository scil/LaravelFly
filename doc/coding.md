# Tips for use

## global vars are not global any more

Global vars are only global in single swoole worker.

Swoole workers run in different process, vars are not shared by different workers. 

Methods to share vars between workers:
* Swoole tools like Table, Channel, ...
* Yac, Redis, Memcached, ...

## php functions useless in LaravelFly

name | replacement
------------ | ------------ 
header | Laravel Api: $response->header
setcookie | Laravel Api: $response->cookie

## Mode Map

Mode Map uses coroutine, so different requests can be handled by server concurrently. Suppose the server is handling a request, meet `co::sleep(3)` , then it goes to handle another request, later go back to the first request.

The basic services have been converted to be Coroutine Friendly and some tests have added to make sure the converted files can follow Laravel's new releases.  

There are some tips:
* Do not use super global vars like $_GET, $_POST, they are different among requests.
* If you use [Laravel Macros](https://tighten.co/blog/the-magic-of-laravel-macros), make sure they are always same in all of the requests. I've not made it Coroutine Friendly because I think in most situations they are always same.
