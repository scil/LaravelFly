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
    use Dict;
    protected static $normalAttriForObj = ['sessionHandled' => false,];

    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
        $this->initOnWorker( true);
    }
    public function handle($request, Closure $next)
    {
        static::$corDict[\Co::getUid()]['sessionHandled'] = true;

        // If a session driver has been configured, we will need to start the session here
        // so that the data is ready for an application. Note that the Laravel sessions
        // do not make use of PHP "native" sessions in any way since they are crappy.
        if ($this->sessionConfigured()) {
            $request->setLaravelSession(
                $session = $this->startSession($request)
            );

            $this->collectGarbage($session);
        }

        $response = $next($request);

        // Again, if the session has been configured we will need to close out the session
        // so that the attributes may be persisted to some storage medium. We will also
        // add the session identifier cookie to the application response headers now.
        if ($this->sessionConfigured()) {
            $this->storeCurrentUrl($request, $session);

            $this->addCookieToResponse($response, $session);
        }

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        if (static::$corDict[\Co::getUid()]['sessionHandled'] && $this->sessionConfigured() && ! $this->usingCookieSessions()) {
            $this->manager->driver()->save();
        }
    }
}
