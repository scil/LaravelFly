<?php

namespace LaravelFly\Map\Illuminate\Session\Swoole;


use DateTime;
use SessionHandlerInterface;

class SwooleSessionHandler implements SessionHandlerInterface
{


    /**
     * @var \swoole_table $table
     */
    var $table;

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    var $minutes;

    public function __construct($table, $minutes)
    {
        $this->table = $table;
        // (int)ini_get('session.gc_maxlifetime') ?
        $this->minutes = $minutes;
    }


    public function close()
    {
        return true;
    }


    public function destroy($session_id)
    {
        $this->table->del($session_id);
    }


    // todo cron job to clean old session?
    public function gc($maxlifetime)
    {
        // require pcre-devel
        foreach ($this->table as $key => $row) {
            /**
             * @var \swoole_table_row $row
             */
            if ($row['last_activity'] > $maxlifetime)
                $this->table->del($key);

        }
    }

    function expired($lasttime)
    {
        return round(time() / 60) - $lasttime > $this->minutes;

    }

    public function open($save_path, $name)
    {
        return true;
    }


    public function read($session_id)
    {
        $data = $this->table->get($session_id);

        if (!$data) return '';

        if ($this->expired($data['last_activity'])) {
            $this->table->del($session_id);
            return '';
        }
        return $data['payload'];
    }


    public function write($session_id, $session_data)
    {
        $this->table->set($session_id, [
            'payload' => $session_data,
            'last_activity' => round(time() / 60)]);
    }


}