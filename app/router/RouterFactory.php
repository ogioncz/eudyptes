<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter() 	{
		$router = new RouteList();
		$router[] = new Route('post/<id \d+>', 'Post:show', Route::ONE_WAY);
		$router[] = new Route('post/<id \d+>/edit', 'Post:edit', Route::ONE_WAY);
		$router[] = new Route('<slug .+>.html', 'Page:show', Route::ONE_WAY);
		$router[] = new Route('user/login', 'Sign:in', Route::ONE_WAY);
		$router[] = new Route('user/logout', 'Sign:out', Route::ONE_WAY);
		$router[] = new Route('user/register', 'Profile:create', Route::ONE_WAY);
		$router[] = new Route('user/edit', 'Profile:edit', Route::ONE_WAY);
		$router[] = new Route('profile/<id \d+>', 'Profile:show', Route::ONE_WAY);
		$router[] = new Route('profile', 'Profile:list', Route::ONE_WAY);
		$router[] = new Route('meeting', 'Meeting:list', Route::ONE_WAY);
		$router[] = new Route('meeting/new', 'Meeting:create', Route::ONE_WAY);
		$router[] = new Route('meeting/<id \d+>/<action (edit|delete)>', ['presenter' => 'Meeting'], Route::ONE_WAY);
		$router[] = new Route('rss.xml', 'Post:rss', Route::ONE_WAY);
		$router[] = new Route('mail', 'Mail:list', Route::ONE_WAY);
		$router[] = new Route('mail/sent', ['presenter' => 'Mail', 'action' => 'list', 'sent' => true], Route::ONE_WAY);
		$router[] = new Route('mail/<id \d+>', 'Mail:show', Route::ONE_WAY);
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}

}
