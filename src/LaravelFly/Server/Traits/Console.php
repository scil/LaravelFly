<?php
/**
 * User: scil
 * Date: 2018/10/18
 * Time: 14:09
 */

namespace LaravelFly\Server\Traits;

use Composer\IO\ConsoleIO;
use Psy\Input\ShellInput;
use Symfony\Component\Console\Output\ConsoleOutput;

trait Console
{
    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    protected function console(){

        // $this->input = new \Symfony\Component\Console\Input\ArgvInput(),
        $input = $this->input = new \Psy\Input\ShellInput('');

        $output = $this->output = new \Illuminate\Console\OutputStyle($input, new ConsoleOutput());

        // vendor/composer/composer/src/Composer/Console/Application.php
        $io = $this->io = new ConsoleIO($input, $output, (new \Symfony\Component\Console\Application())->getHelperSet());

//        if ($this->input->isInteractive()) {
//            $description = $io->ask('Are you sure to go on? ', 'n');
//            $this->output->writeln($description);
//        }
    }
    function echo($text, $status = 'INFO', $color = false)
    {

        switch ($status) {
            case 'INFO':
                $level = 3;
                break;
            case 'NOTE':
                $level = 2;
                break;
            case 'WARN':
                $level = 1;
                break;
            case 'ERR':
                $level = 0;
                break;
            default:
                $level = 0;
        }
        if ($level <= $this->echoLevel) {
            $text = "[$status] $text\n";
            echo $color ? $this->colorize($text, $status) : $text;
        }

    }

    function echoOnce($text, $status = 'INFO', $color = false)
    {
        if ($this->currentWorkerID === 0) {
            $this->echo($text, $status, $color);
        }
    }

    function colorize($text, $status)
    {
        if (!$this->colorize) return $text;

        $out = "";
        switch ($status) {
            case "WARN":
                $out = "[41m"; //Red background
                break;
            case "NOTE":
                $out = "[43m"; //Yellow background
                // $out = "[44m"; //Blue background
                break;
            case "SUCCESS":
                $out = "[42m"; //Green background
                break;
            case "ERR":
                $out = "[41m"; //Red background
                break;
            default:
                throw new \Exception("Invalid status: " . $status);
        }
        return chr(27) . "$out" . "$text" . chr(27) . "[0m";
    }

}