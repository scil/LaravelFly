
public function read($sessionId)
{
$session = (object) $this->getQuery()->find($sessionId);

if ($this->expired($session)) {
$this->exists = true;

return '';
}

if (isset($session->payload)) {
$this->exists = true;

return base64_decode($session->payload);
}

return '';
}



===A===



public function write($sessionId, $data)
{
$payload = $this->getDefaultPayload($data);

if (! $this->exists) {
$this->read($sessionId);
}

if ($this->exists) {
$this->performUpdate($sessionId, $payload);
} else {
$this->performInsert($sessionId, $payload);
}

return $this->exists = true;
}




===A===



public function setExists($value)
{
$this->exists = $value;

return $this;
}



