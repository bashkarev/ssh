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
class Client
{
    /**
     * @var string
     */
    private $defaultArguments = '-o StrictHostKeyChecking=no';
    /**
     * @var string
     */
    private $hostName;
    /**
     * @var string
     */
    private $user;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string|null
     */
    private $identityFile;
    /**
     * @var bool
     */
    private $forwardAgent = false;
    /**
     * @var Pipes
     */
    private $pipes;


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
     * @param string $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setIdentityFile($file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Identity file `{$file}` not found");
        }
        $this->identityFile = $file;

        return $this;
    }

    /**
     * @param string|null $arguments
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
     * @param bool $forwardAgent
     * @return $this
     */
    public function setForwardAgent($forwardAgent = true)
    {
        $this->forwardAgent = (bool)$forwardAgent;

        return $this;
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