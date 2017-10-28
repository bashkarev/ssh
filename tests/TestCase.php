<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh\Tests;

use Bashkarev\Ssh\Pipes;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Pipes
     */
    protected function getPipeMock()
    {
        $stub = $this->createMock(Pipes::class);
        $stub->method('write')->willReturnCallback(function ($cmd) {
            return strlen($cmd);
        });
        $stub->method('open')->willReturn(null);

        return $stub;
    }

}