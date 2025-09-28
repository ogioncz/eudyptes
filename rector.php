<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/app',
		__DIR__ . '/tests',
		__DIR__ . '/www',
	])
	->withPhpSets()
	->withTypeCoverageLevel(0);
