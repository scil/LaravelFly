
protected function getPathAndDomain($path, $domain, $secure = false, $sameSite = null)
{
    return [$path ?: $this->path, $domain ?: $this->domain, $secure ?: $this->secure, $sameSite ?: $this->sameSite];
}

===A===

public function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
{
list($this->path, $this->domain, $this->secure, $this->sameSite) = [$path, $domain, $secure, $sameSite];

return $this;
}

