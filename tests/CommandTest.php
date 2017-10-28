<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh\Tests;

use Bashkarev\Ssh\Command;

class CommandTest extends TestCase
{

    public function testConstructor()
    {
        $command = new Command('php -v', $this->getPipeMock());
        $this->assertSame('php -v', $command->getCommandLine());
        $this->assertSame(60.00, $command->getTimeout());
        $this->assertNull($command->getIdleTimeout());
        $this->assertNull($command->getExitCode());
    }

    /**
     * @expectedException \Bashkarev\Ssh\Exception\InvalidArgumentException
     */
    public function testTimeoutNegative()
    {
        new Command('php -v', $this->getPipeMock(), -1);
    }

    /**
     * @expectedException \Bashkarev\Ssh\Exception\InvalidArgumentException
     */
    public function testIdleTimeoutNegative()
    {
        new Command('php -v', $this->getPipeMock(), 60, -1);
    }

}