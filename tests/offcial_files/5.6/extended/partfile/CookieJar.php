
protected function getPathAndDomain($path, $domain, $secure = null, $sameSite = null)
{
    return [$path ?: $this->path, $domain ?: $this->domain, is_bool($secure) ? $secure : $this->secure, $sameSite ?: $this->sameSite];
}

===A===

public function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
{
list($this->path, $this->domain, $this->secure, $this->sameSite) = [$path, $domain, $secure, $sameSite];

return $this;
}


===A===


{
use InteractsWithTime;

/**
* The default path (if specified).
*
* @var string
*/
protected $path = '/';

/**
* The default domain (if specified).
*
* @var string
*/
protected $domain;

/**
* The default secure setting (defaults to false).
*
* @var bool
*/
protected $secure = false;

/**
* The default SameSite option (if specified).
*
* @var string
*/
protected $sameSite;

/**
* All of the cookies queued for sending.
*
* @var \Symfony\Component\HttpFoundation\Cookie[]
*/
protected $queued = [];

/**
* Create a new cookie instance.
*
* @param  string       $name
* @param  string       $value
* @param  int          $minutes
* @param  string       $path
* @param  string       $domain
* @param  bool|null    $secure
* @param  bool         $httpOnly
* @param  bool         $raw
* @param  string|null  $sameSite
* @return \Symfony\Component\HttpFoundation\Cookie
*/
public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $raw = false, $sameSite = null)
