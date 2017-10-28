<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh\Exception;

use Bashkarev\Ssh\Command;

/**
 * @author Dmitry Bashkarev <dmitry@bashkarev.com>
 */
class TimedOutException extends \RuntimeException
{
    const TYPE_GENERAL = 1;
    const TYPE_IDLE = 2;
    /**
     * @var Command
     */
    private $command;
    /**
     * @var int
     */
    private $timeoutType;

    public function __construct(Command $command, $timeoutType)
    {
        $this->command = $command;
        $this->timeoutType = $timeoutType;
        parent::__construct(sprintf(
            'The command "%s" exceeded the timeout of %s seconds.',
            $command->getCommandLine(),
            $this->getExceededTimeout()
        ));
    }

    /**
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return bool
     */
    public function isGeneralTimeout()
    {
        return self::TYPE_GENERAL === $this->timeoutType;
    }

    /**
     * @return bool
     */
    public function isIdleTimeout()
    {
        return self::TYPE_IDLE === $this->timeoutType;
    }

    /**
     * @return float
     * @throws \LogicException
     */
    public function getExceededTimeout()
    {
        switch ($this->timeoutType) {
            case self::TYPE_GENERAL:
                return $this->command->getTimeout();
            case self::TYPE_IDLE:
                return $this->command->getIdleTimeout();
            default:
                throw new \LogicException(sprintf('Unknown timeout type "%d".', $this->timeoutType));
        }
    }
}