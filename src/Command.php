<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh;

/**
 * @author Dmitry Bashkarev <dmitry@bashkarev.com>
 */
class Command
{
    /**
     * @var Pipes
     */
    private $pipes;
    /**
     * @var int
     */
    private $exitCode;
    /**
     * @var float|null
     */
    private $timeout;
    /**
     * @var float|null
     */
    private $idleTimeout;
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
     * @param string $command
     * @param Pipes $pipes
     * @param int|null $timeout
     * @param int|null $idleTimeout
     */
    public function __construct($command, Pipes $pipes, $timeout = 60, $idleTimeout = null)
    {
        $this->startTime = microtime(true);
        $this->pipes = $pipes;
        $this->timeout = $timeout;
        $this->idleTimeout = $idleTimeout;

        $this->pipes->write(rtrim($command) . ';echo -ne "[return_code:$?]"' . PHP_EOL);
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
                throw new \Exception('Connection failed'); // todo last error && check
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
            throw new \RuntimeException('timeout');//todo ProcessTimedOutException
        }

        if (null !== $this->idleTimeout && $this->idleTimeout < microtime(true) - $this->lastOutputTime) {
            throw new \RuntimeException('timeout');//todo ProcessTimedOutException
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

}