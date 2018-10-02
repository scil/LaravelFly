<?php
/**
 * User: scil
 * Date: 2018/10/1
 * Time: 18:28
 */

namespace LaravelFly\Tools\SessionTablePipe;


class PlainFilePipe extends Pipe
{
    /**
     * @var string $dumpFile
     */
    protected $dumpFile = '';

    function __construct($table, $file)
    {
        $this->table = $table;
        $this->dumpFile = $file;
    }

    public function getDumpFile(): string
    {
        return $this->dumpFile;
    }

    function restore()
    {
        if (!$file = $this->dumpFile) {
            return;
        }

        if (!\file_exists($file)) {
            return;
        }

        $content = \file_get_contents($file);
        $this->table->load((array)\json_decode($content, true));
    }

    function dump()
    {

        if (!$file = $this->dumpFile) {
            return;
        }

        $data = [];

        foreach ($this->table as $key => $row) {
            if ($this->table->notExpired($row['last_activity']))
                $data[$key] = $row;
        }


        \file_put_contents($file, \json_encode($data));
    }
}