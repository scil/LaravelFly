
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



===A===


{
use InteractsWithTime;

/**
* The database connection instance.
*
* @var \Illuminate\Database\ConnectionInterface
*/
protected $connection;

/**
* The name of the session table.
*
* @var string
*/
protected $table;

/**
* The number of minutes the session should be valid.
*
* @var int
*/
protected $minutes;

/**
* The container instance.
*
* @var \Illuminate\Contracts\Container\Container
*/
protected $container;

/**
* The existence state of the session.
*
* @var bool
*/
protected $exists;

/**
* Create a new database session handler instance.
*
* @param  \Illuminate\Database\ConnectionInterface  $connection
* @param  string  $table
* @param  int  $minutes
* @param  \Illuminate\Contracts\Container\Container|null  $container
* @return void
*/
public function __construct(ConnectionInterface $connection, $table, $minutes, Container $container = null)
