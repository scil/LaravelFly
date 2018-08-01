
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

