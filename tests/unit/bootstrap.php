<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Environment;

require __DIR__ . '/../../vendor/autoload.php';

Environment::setup();

$configurator = new Configurator();
$configurator->setDebugMode(false);
$configurator->setTempDirectory(__DIR__ . '/../../temp');

$configurator->addConfig(__DIR__ . '/../../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../../app/config/config.local.neon');

return $configurator->createContainer();
