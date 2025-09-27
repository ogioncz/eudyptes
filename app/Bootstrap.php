<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;
use Nette\DI\Container;

require_once __DIR__ . '/../vendor/autoload.php';

final class Bootstrap {
	private Configurator $configurator;
	private string $rootDir;

	public function __construct() {
		$this->rootDir = \dirname(__DIR__);
		$this->configurator = new Configurator();

		$this->configurator->setTempDirectory($this->rootDir . '/temp');
	}

	public function bootWebApplication(): Container {
		$this->initializeEnvironment();
		$this->setupContainer();

		$container = $this->configurator->createContainer();

		return $container;
	}

	public function initializeEnvironment(): void {
		// $this->configurator->setDebugMode(TRUE);  // debug mode MUST NOT be enabled on production server
		$this->configurator->enableTracy($this->rootDir . '/log');
	}

	private function setupContainer(): void {
		$configDir = $this->rootDir . '/app/config';
		$this->configurator->addConfig($configDir . '/config.neon');
		$this->configurator->addConfig($configDir . '/config.local.neon');
	}
}
