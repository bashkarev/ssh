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
class Pipes
{
    const ERR = 'err';
    const OUT = 'out';

    /**
     * @var resource
     */
    private $process;
    /**
     * @var array
     */
    private $pipes;

    /**
     * @param string $ssh
     * @throws \Exception
     */
    public function open($ssh)
    {
        if (is_resource($this->process)) {
            return;
        }

        $descriptor = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $this->process = proc_open($ssh, $descriptor, $this->pipes);
        if (false === $this->process) {
            $message = sprintf('Failed to execute the command: "%s".', $ssh);
            if ($error = error_get_last()) {
                $message .= sprintf(' Errno: %d; %s', $error['type'], $error['message']);
            }
            throw new \Exception($message);
        }

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);
    }

    /**
     * @param $cmd
     * @return bool|int
     */
    public function write($cmd)
    {
        return fwrite($this->pipes[0], $cmd);
    }

    /**
     * @return resource[]
     */
    public function getRead()
    {
        return [
            self::OUT => $this->pipes[1],
            self::ERR => $this->pipes[2],
        ];
    }

    /**
     * Close connection
     */
    public function close()
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        proc_close($this->process);
    }

}