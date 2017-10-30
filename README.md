Native ssh client 
=================
status: in progress

[![Build Status](https://travis-ci.org/bashkarev/ssh.svg?branch=master)](https://travis-ci.org/bashkarev/ssh)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require bashkarev/ssh
```
 
## Usage

```php
$client = new \Bashkarev\Ssh\Client('127.0.0.1');
$client
    ->setPort(22)
    ->setUser('ssh_user')
    ->setIdentityFile('path/to/private_key')
    ->setForwardAgent(true);

/**
 * @var \Bashkarev\Ssh\Command $command
 */
$command = $client->exec('php -v', 360, 60);
foreach ($command->getIterator() as $type => $data) {
    if ($command::OUT === $type) {
        echo "\nRead from stdout: " . $data;
    } else { // $command::ERR === $type
        echo "\nRead from stderr: " . $data;
    }
}

$command->getExitCode();

```