<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

$configurator = new \Nette\Bootstrap\Configurator;
$configurator->setDebugMode(false);
$configurator->setTempDirectory(__DIR__ . '/../../temp');

$configurator->addConfig(__DIR__ . '/../../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../../app/config/config.local.neon');
return $configurator->createContainer();
