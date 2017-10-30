<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh;

use Bashkarev\Ssh\Exception\InvalidArgumentException;
use Bashkarev\Ssh\Exception\TimedOutException;

/**
 * @author Dmitry Bashkarev <dmitry@bashkarev.com>
 */
class Command
{
    const ERR = 'err';
    const OUT = 'out';

    /**
     * @var Pipes
     */
    private $pipes;
    /**
     * @var string
     */
    private $commandLine;
    /**
     * @var float|null
     */
    private $timeout;
    /**
     * @var float|null
     */
    private $idleTimeout;
    /**
     * @var int
     */
    private $exitCode;
    /**
     * @var float
     */
    private $startTime;
    /**
     * @var float
     */
    private $lastOutputTime;

    /**
     * Command constructor.
     * @param string $commandLine
     * @param Pipes $pipes
     * @param int|float|null $timeout
     * @param int|float|null $idleTimeout
     */
    public function __construct($commandLine, Pipes $pipes, $timeout = 60, $idleTimeout = null)
    {
        $this->commandLine = $commandLine;
        $this->startTime = microtime(true);
        $this->pipes = $pipes;
        $this->timeout = $this->validateTimeout($timeout);
        $this->idleTimeout = $this->validateTimeout($idleTimeout);

        $this->pipes->write(rtrim($commandLine) . ';echo -ne "[return_code:$?]"' . PHP_EOL);
    }

    /**
     * @return string
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }

    /**
     * @return float|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return float|null
     */
    public function getIdleTimeout()
    {
        return $this->idleTimeout;
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function getIterator()
    {
        $write = $except = [];
        $read = $this->pipes->getRead();

        while (true) {
            $r = $read;
            if (false === @stream_select($r, $write, $except, 0, $this->calcTvMicrosecond())) {
                $message = 'Failed stream select.';
                if ($error = error_get_last()) {
                    $message .= sprintf(' Errno: %d; %s', $error['type'], $error['message']);
                }
                throw new \Exception($message);
            }

            foreach ($r as $pipe) {
                $type = array_search($pipe, $read, true);

                while ($buf = fread($pipe, 1000)) {

                    if (false !== strpos($buf, '[return_code')) {
                        /**
                         * replace echo
                         */
                        $buf = preg_replace('/.*return_code:\$\?\]"(\n|\r\n|)/s', '', $buf);
                        if ('' === $buf) {
                            continue;
                        }

                        if (preg_match('/(.*)\[return_code:(\d+)\]/s', $buf, $out)) {

                            yield $type => $out[1];

                            $this->exitCode = (int)$out[2];
                            return;
                        }
                    }
                    $this->lastOutputTime = microtime(true);

                    yield $type => $buf;
                }
            }

            $this->checkTimeout();
        }

    }

    /**
     * @return int|null
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @throws \RuntimeException
     */
    public function checkTimeout()
    {
        if (null !== $this->timeout && $this->timeout < microtime(true) - $this->startTime) {
            throw new TimedOutException($this, TimedOutException::TYPE_GENERAL);
        }

        if (null !== $this->lastOutputTime && null !== $this->idleTimeout && $this->idleTimeout < microtime(true) - $this->lastOutputTime) {
            throw new TimedOutException($this, TimedOutException::TYPE_IDLE);
        }

    }

    /**
     * @return float|int
     */
    protected function calcTvMicrosecond()
    {
        $timer = 3E7;

        if (null !== $this->timeout) {
            $timer = ($this->timeout - (microtime(true) - $this->startTime)) * 1000000;
        }

        if (null !== $this->idleTimeout && null !== $this->lastOutputTime) {
            $timerIdleTimeout = ($this->idleTimeout - (microtime(true) - $this->lastOutputTime)) * 1000000;
            if ($timerIdleTimeout < $timer) {
                $timer = $timerIdleTimeout;
            }
        }

        return ($timer < 0) ? 0 : $timer;
    }

    /**
     * Validates and returns the filtered timeout.
     *
     * @param int|float|null $timeout
     * @return float|null
     * @throws InvalidArgumentException if the given timeout is a negative number
     */
    private function validateTimeout($timeout)
    {
        if (null === $timeout) {
            return null;
        }

        $timeout = (float)$timeout;
        if (0.0 === $timeout) {
            $timeout = null;
        } elseif ($timeout < 0) {
            throw new InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        return $timeout;
    }
}