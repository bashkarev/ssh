<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh;

use Bashkarev\Ssh\Exception\InvalidArgumentException;

/**
 * @author Dmitry Bashkarev <dmitry@bashkarev.com>
 */
class Client
{
    /**
     * @var string
     */
    private $hostName;
    /**
     * @var null|string
     */
    private $user;
    /**
     * @var int
     */
    private $port = 22;
    /**
     * @var null|string
     */
    private $identityFile;
    /**
     * @var null|string
     */
    private $defaultArguments = '-o StrictHostKeyChecking=no';
    /**
     * @var bool
     */
    private $forwardAgent = false;
    /**
     * @var Pipes
     */
    private $pipes;

    /**
     * @return string ssh build command
     */
    public function __toString()
    {
        $ssh = 'ssh -tt';
        if (null !== $this->defaultArguments) {
            $ssh .= " {$this->defaultArguments}";
        }
        if (null !== $this->identityFile) {
            $ssh .= " -i {$this->identityFile}";
        }
        if (true === $this->forwardAgent) {
            $ssh .= ' -A';
        }
        if (null !== $this->port) {
            $ssh .= " -p {$this->port}";
        }

        $ssh .= (null !== $this->user) ? " {$this->user}@{$this->hostName}" : " {$this->hostName}";

        return $ssh;
    }

    /**
     * Client constructor.
     * @param string $hostName
     */
    public function __construct($hostName = '127.0.0.1')
    {
        $this->hostName = $hostName;
    }

    /**
     * @param string $hostName
     * @return $this
     */
    public function setHostName($hostName)
    {
        $this->hostName = $hostName;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostName()
    {
        return $this->hostName;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $port According to RFC 793, range is 0 - 65535.
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setPort($port)
    {
        $port = (int)$port;
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException('The port value must be a real positive integer, in the range 0-65535.');
        }
        $this->port = $port;

        return $this;
    }

    /**
     * @return int According to RFC 793, range is 0 - 65535.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $file file path
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setIdentityFile($file)
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException("Identity file `{$file}` not found");
        }
        $this->identityFile = $file;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getIdentityFile()
    {
        return $this->identityFile;
    }

    /**
     * @param null|string $arguments
     * @return $this
     */
    public function setDefaultArguments($arguments)
    {
        if (null === $arguments) {
            $this->defaultArguments = null;
        } else {
            $this->defaultArguments = trim($arguments);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultArguments()
    {
        return $this->defaultArguments;
    }

    /**
     * @param bool $forwardAgent
     * @return $this
     */
    public function setForwardAgent($forwardAgent = true)
    {
        $this->forwardAgent = (bool)$forwardAgent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForwardAgent()
    {
        return $this->forwardAgent;
    }

    /**
     * @param string $command
     * @param int|null $timeout
     * @param int|null $idleTimeout
     * @return Command
     */
    public function exec($command, $timeout = 60, $idleTimeout = null)
    {
        if (null === $this->pipes) {
            $this->pipes = new Pipes();
            $this->pipes->open($this->__toString());
        }

        return new Command($command, $this->pipes, $timeout, $idleTimeout);
    }

}