<?php
/**
 * @copyright Copyright (c) 2017 Dmitry Bashkarev
 * @license https://github.com/bashkarev/ssh/blob/master/LICENSE
 * @link https://github.com/bashkarev/ssh#readme
 */

namespace Bashkarev\Ssh\Tests;

use Bashkarev\Ssh\Client;

class ClientTest extends TestCase
{

    public function testConstructor()
    {
        $this->assertSame('127.0.0.1', (new Client())->getHostName());
        $this->assertSame('example.com', (new Client('example.com'))->getHostName());
    }

    public function testHostName()
    {
        $this->assertSame('example.com', (new Client())->setHostName('example.com')->getHostName());
    }

    public function testUser()
    {
        $client = new Client();
        $this->assertNull($client->getUser());
        $this->assertSame('ssh', $client->setUser('ssh')->getUser());
    }

    public function testPort()
    {
        $client = new Client();
        $this->assertSame(22, $client->getPort());
        $this->assertSame(65535, $client->setPort('65535')->getPort());
    }

    /**
     * @expectedException \Bashkarev\Ssh\Exception\InvalidArgumentException
     */
    public function testPortNegative()
    {
        (new Client())->setPort(-1);
    }

    /**
     * @expectedException \Bashkarev\Ssh\Exception\InvalidArgumentException
     */
    public function testPortAbove()
    {
        (new Client())->setPort(65536);
    }

    public function testIdentityFile()
    {
        $client = new Client();
        $this->assertNull($client->getIdentityFile());
        $this->assertSame(__FILE__, $client->setIdentityFile(__FILE__)->getIdentityFile());
    }

    /**
     * @expectedException \Bashkarev\Ssh\Exception\InvalidArgumentException
     */
    public function testIdentityFileNotExist()
    {
        (new Client())->setIdentityFile('none');
    }

    public function testDefaultArguments()
    {
        $client = new Client();
        $this->assertSame('-o StrictHostKeyChecking=no', $client->getDefaultArguments());
        $this->assertSame('-o UserKnownHostsFile=/dev/null', $client->setDefaultArguments(' -o UserKnownHostsFile=/dev/null ')->getDefaultArguments());
    }

    public function testForwardAgent()
    {
        $client = new Client();
        $this->assertFalse($client->isForwardAgent());
        $this->assertTrue($client->setForwardAgent(true)->isForwardAgent());
    }

    /**
     * @dataProvider buildData()
     * @param array $options
     * @param string $needle
     * @param string $message
     */
    public function testBuild($options, $needle, $message)
    {
        $client = new Client();
        foreach ($options as $option => $value) {
            $client->{'set' . $option}($value);
        }
        $this->assertContains($needle, $client->__toString(), $message);
    }

    /**
     * @return array
     */
    public function buildData()
    {
        $file = __FILE__;
        return [
            [[], 'ssh -tt -o StrictHostKeyChecking=no -p 22 127.0.0.1', '#1, default'],
            [
                [
                    'hostName' => 'example.com',
                    'user' => 'bashkarev',
                    'port' => 777,
                    'identityFile' => $file,
                    'defaultArguments' => '-o UserKnownHostsFile=/dev/null',
                    'forwardAgent' => true
                ], "ssh -tt -o UserKnownHostsFile=/dev/null -i {$file} -A -p 777 bashkarev@example.com", '#2, all'],
            [['defaultArguments' => null], 'ssh -tt -p 22 127.0.0.1', '#3, null default arguments'],
        ];
    }


}