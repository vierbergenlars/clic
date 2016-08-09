<?php

/**
 * clic, user-friendly PHP application deployment and set-up
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Lars Vierbergen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace vierbergenlars\CliCentral\Helper;

use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * The ProcessHelper class provides helpers to run external processes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ProcessHelper extends Helper
{
    /**
     * Runs an external process.
     *
     * @param OutputInterface      $output    An OutputInterface instance
     * @param string|array|Process $cmd       An instance of Process or an array of arguments to escape and run or a command to run
     * @param string|null          $error     An error message that must be displayed if something went wrong
     * @param callable|null        $callback  A PHP callback to run whenever there is some
     *                                        output available on STDOUT or STDERR
     * @param int                  $verbosity The threshold for verbosity
     * @param int                  $lineVerbosity Threshold for verbosity to show all output of the process
     *
     * @return Process The process that ran
     */
    public function run(OutputInterface $output, $cmd, $error = null, callable $callback = null, $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE, $lineVerbosity = OutputInterface::VERBOSITY_DEBUG)
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $formatter = $this->getHelperSet()->get('debug_formatter');
        /* @var $formatter DebugFormatterHelper */

        if (is_array($cmd)) {
            $process = ProcessBuilder::create($cmd)->getProcess();
        } elseif ($cmd instanceof Process) {
            $process = $cmd;
        } else {
            $process = new Process($cmd);
        }

        if ($verbosity <= $output->getVerbosity()) {
            $output->write($formatter->start(spl_object_hash($process), $this->escapeString($process->getCommandLine())));
            $output->write($formatter->progress(spl_object_hash($process), $this->escapeString($process->getWorkingDirectory()), false, 'DIR'));
            $output->write($formatter->progress(spl_object_hash($process), PHP_EOL));
        }

        if ($lineVerbosity <= $output->getVerbosity()) {
            $callback = $this->wrapCallback($output, $process, $callback);
        }

        $process->run($callback);

        if ($verbosity <= $output->getVerbosity()) {
            $message = $process->isSuccessful() ? 'Command ran successfully' : sprintf('%s Command did not run successfully', $process->getExitCode());
            $output->write($formatter->stop(spl_object_hash($process), $message, $process->isSuccessful()));
        }

        if (!$process->isSuccessful() && null !== $error) {
            $output->writeln(sprintf('<error>%s</error>', $this->escapeString($error)));
        }

        return $process;
    }

    /**
     * Runs the process.
     *
     * This is identical to run() except that an exception is thrown if the process
     * exits with a non-zero exit code.
     *
     * @param OutputInterface $output   An OutputInterface instance
     * @param string|Process  $cmd      An instance of Process or a command to run
     * @param string|null     $error    An error message that must be displayed if something went wrong
     * @param callable|null   $callback A PHP callback to run whenever there is some
     *                                  output available on STDOUT or STDERR
     * @param int             $verbosity The threshold for verbosity
     * @param int             $lineVerbosity Threshold for verbosity to show all output of the process
     *
     * @return Process The process that ran
     *
     * @throws ProcessFailedException
     *
     * @see run()
     */
    public function mustRun(OutputInterface $output, $cmd, $error = null, callable $callback = null, $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE, $lineVerbosity = OutputInterface::VERBOSITY_DEBUG)
    {
        $process = $this->run($output, $cmd, $error, $callback, $verbosity, $lineVerbosity);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * Wraps a Process callback to add debugging output.
     *
     * @param OutputInterface $output   An OutputInterface interface
     * @param Process         $process  The Process
     * @param callable|null   $callback A PHP callable
     *
     * @return callable
     */
    public function wrapCallback(OutputInterface $output, Process $process, callable $callback = null)
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $formatter = $this->getHelperSet()->get('debug_formatter');

        return function ($type, $buffer) use ($output, $process, $callback, $formatter) {
            $output->write($formatter->progress(spl_object_hash($process), $this->escapeString($buffer), Process::ERR === $type));

            if (null !== $callback) {
                call_user_func($callback, $type, $buffer);
            }
        };
    }

    private function escapeString($str)
    {
        return str_replace('<', '\\<', $str);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'process';
    }
}
