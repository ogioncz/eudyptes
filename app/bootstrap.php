<?php

declare(strict_types=1);

use Nette\Forms\Container;
use Nextras\FormComponents\Controls\DateControl;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Bootstrap\Configurator;

// $configurator->setDebugMode(TRUE);  // debug mode MUST NOT be enabled on production server
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

Container::extensionMethod('addDatePicker', fn(Container $container, $name, $label = null) => $container[$name] = new DateControl($label));
Container::extensionMethod('addTimePicker', fn(Container $container, $name, $label = null) => $container[$name] = new App\Components\TimePicker($label));
Kdyby\Replicator\Container::register();

return $container;
