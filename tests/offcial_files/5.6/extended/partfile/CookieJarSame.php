public function queued($key, $default = null)
{
return Arr::get($this->queued, $key, $default);
}

===A===

public function queue(...$parameters)
{
if (head($parameters) instanceof Cookie) {
$cookie = head($parameters);
} else {
$cookie = call_user_func_array([$this, 'make'], $parameters);
}

$this->queued[$cookie->getName()] = $cookie;
}

===A===

public function unqueue($name)
{
unset($this->queued[$name]);
}

===A===

public function getQueuedCookies()
{
return $this->queued;
}
