
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
