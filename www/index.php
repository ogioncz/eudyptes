<?php

declare(strict_types=1);

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

require __DIR__ . '/../vendor/autoload.php';

$bootstrap = new App\Bootstrap();
$container = $bootstrap->bootWebApplication();
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
