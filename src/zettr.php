<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Zettr\Command\ApplyCommand;
use Symfony\Component\Console\Application;

$app = new Application('Zettr', '@package_version@');

$app->add(new ApplyCommand);

$app->run();