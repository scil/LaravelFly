<?php
/**
 * only for option 'log_cache'.
 */

namespace LaravelFly\Map\CommonHack;



use Monolog\Logger;
use Symfony\Component\EventDispatcher\GenericEvent;

trait StreamHandlerLogCache
{
    private $flyCache = [];

    /**
     * @var int
     */
    private $flyCacheMax = 5;


    /**
     * @param resource|string $stream
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool            $bubble         Whether the messages that are handled can bubble up the stack or not
     * @param int|null         $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param bool            $useLocking Try to lock log file before doing any writes
     *
     * @throws \Exception                If a missing directory is not buildable
     * @throws \InvalidArgumentException If stream is not a resource or string
     *
     * @overwrite
     */
    public function __construct($stream, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = $stream;
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;


        $this->flyCacheMax = \LaravelFly\Fly::getServer()->getConfig('log_cache') ?? 5;

        // why?
        // $this->__destruct();  will call $this->>close();
        // but the sad thing is that: $this->>__destruct not called when server is reloaded or restarted,
        // so i need call it manuly
        $dispatcher = \LaravelFly\Fly::getServer()->getDispatcher();
        $dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
            if ($this->flyCache)
                $this->cacheWrite();
        });

    }

    /**
     * {@inheritdoc}
     *
     * @overwrite
     */
    protected function write(array $record)
    {
        if (!is_resource($this->stream)) {
            if (null === $this->url || '' === $this->url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->createDir();
            $this->errorMessage = null;
            set_error_handler(array($this, 'customErrorHandler'));
            $this->stream = fopen($this->url, 'a');
            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }
            restore_error_handler();
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $this->url));
            }
        }

        $this->flyCache[] = (string)$record['formatted'];

        if (count($this->flyCache) < $this->flyCacheMax) {
            return;
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($this->stream, LOCK_EX);
        }

        // hack: Cache for Log
        //$this->streamWrite($this->stream, $record);
        $this->cacheWrite();

        if ($this->useLocking) {
            flock($this->stream, LOCK_UN);
        }
    }


    /**
     * @overwrite
     */
    public function cacheWrite()
    {
        fwrite($this->stream, implode('', $this->flyCache) . PHP_EOL);
        $this->flyCache = [];
    }

    /**
     * @param int $flyCacheMax
     */
    public function setFlyCacheMax(int $flyCacheMax)
    {
        $this->flyCacheMax = $flyCacheMax;
    }

}