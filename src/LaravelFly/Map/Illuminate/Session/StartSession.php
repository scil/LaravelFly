<?php


namespace LaravelFly\Map\Illuminate\Session;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\CookieSessionHandler;
use LaravelFly\Map\Util\Dict;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class StartSession extends \Illuminate\Session\Middleware\StartSession
{
/*    use Dict;
    // protected static $normalAttriForObj = ['sessionHandled' => false,];

    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
        $this->initOnWorker( true);
    }
*/ 
}
