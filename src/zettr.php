<?php

$included = include file_exists(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../../../autoload.php';

if (! $included) {
    echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL
        . 'curl -sS https://getcomposer.org/installer | php' . PHP_EOL
        . 'php composer.phar install' . PHP_EOL;

    exit(1);
}

use Zettr\Command\ApplyCommand;
use Symfony\Component\Console\Application;

$app = new Application('Zettr', '@package_version@');

$app->add(new ApplyCommand);

$app->run();