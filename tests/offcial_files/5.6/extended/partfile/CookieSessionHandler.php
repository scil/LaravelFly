
public function read($sessionId)
{
$value = $this->request->cookies->get($sessionId) ?: '';

if (! is_null($decoded = json_decode($value, true)) && is_array($decoded)) {
if (isset($decoded['expires']) && $this->currentTime() <= $decoded['expires']) {
return $decoded['data'];
}
}

return '';
}


===A===

public function setRequest(Request $request)
{
$this->request = $request;
}

===A===


{
use InteractsWithTime;

/**
* The cookie jar instance.
*
* @var \Illuminate\Contracts\Cookie\Factory
*/
protected $cookie;

/**
* The request instance.
*
* @var \Symfony\Component\HttpFoundation\Request
*/
protected $request;

/**
* The number of minutes the session should be valid.
*
* @var int
*/
protected $minutes;

/**
* Create a new cookie driven handler instance.
*
* @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookie
* @param  int  $minutes
* @return void
*/
public function __construct(CookieJar $cookie, $minutes)
