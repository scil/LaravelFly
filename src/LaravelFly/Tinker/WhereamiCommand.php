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
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 13);

        return end($backtrace);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        // It's not good for these lines to put here, but handy.
        $output->startPaging();
        $output->writeln(sprintf('Tip <info>%s.%s</info>:', 'All info available in this shell are about only objects in current worker process', 'PID:'.getmypid()));
        $output->writeln('');
        $output->stopPaging();
    }
}
