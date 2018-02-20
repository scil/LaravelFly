<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelFly\Tinker;

use JakubOnderka\PhpConsoleHighlighter\Highlighter;
use Psy\Configuration;
use Psy\ConsoleColorFactory;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show the context of where you opened the debugger.
 */
class WhereamiCommand extends \Psy\Command\WhereamiCommand
{

    /**
     * Obtains the correct stack frame in the full backtrace.
     *
     * @return array
     */
    protected function trace()
    {
        foreach (array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)) as $stackFrame) {
            if ($this->isDebugCall($stackFrame)) {
                return $stackFrame;
            }
        }

        return end($backtrace);
    }

    protected static function isDebugCall(array $stackFrame)
    {
        $class = isset($stackFrame['class']) ? $stackFrame['class'] : null;
        $function = isset($stackFrame['function']) ? $stackFrame['function'] : null;

        return ($class === 'LaravelFly\Tinker\Shell' && in_array($function, array('__construct', 'debug'))) ||
            ($class === null && $function === 'tinker');
    }

}
