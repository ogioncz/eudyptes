<?php

declare(strict_types=1);

namespace App;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory {
	public function createRouter(): RouteList {
		$router = new RouteList();
		$router->addRoute('post/<id \d+>', 'Post:show', Route::ONE_WAY);
		$router->addRoute('post/<id \d+>/edit', 'Post:edit', Route::ONE_WAY);
		$router->addRoute('<slug .+>.html', 'Page:show', Route::ONE_WAY);
		$router->addRoute('user/login', 'Sign:in', Route::ONE_WAY);
		$router->addRoute('user/logout', 'Sign:out', Route::ONE_WAY);
		$router->addRoute('user/register', 'Profile:create', Route::ONE_WAY);
		$router->addRoute('user/edit', 'Profile:edit', Route::ONE_WAY);
		$router->addRoute('profile/<id \d+>', 'Profile:show', Route::ONE_WAY);
		$router->addRoute('profile', 'Profile:list', Route::ONE_WAY);
		$router->addRoute('meeting', 'Meeting:list', Route::ONE_WAY);
		$router->addRoute('meeting/new', 'Meeting:create', Route::ONE_WAY);
		$router->addRoute('meeting/<id \d+>/<action (edit|delete)>', ['presenter' => 'Meeting'], Route::ONE_WAY);
		$router->addRoute('rss.xml', 'Post:rss', Route::ONE_WAY);
		$router->addRoute('mail', 'Mail:list', Route::ONE_WAY);
		$router->addRoute('mail/sent', ['presenter' => 'Mail', 'action' => 'list', 'sent' => true], Route::ONE_WAY);
		$router->addRoute('mail/<id \d+>', 'Mail:show', Route::ONE_WAY);
		$router->addRoute('page/show/<slug .*>', ['presenter' => 'Page', 'action' => 'show']);
		$router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}
}
