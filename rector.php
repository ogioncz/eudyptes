<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;

return static function(RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/app',
		__DIR__ . '/tests',
	]);

	$rectorConfig->importNames();

	$parameters = $rectorConfig->parameters();
	$parameters->set(Option::IMPORT_SHORT_CLASSES, false);
};
