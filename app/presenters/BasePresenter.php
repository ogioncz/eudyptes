<?php

namespace App\Presenters;

use Nette,
	Nette\Utils\Html,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	protected function createComponentPaginator($name) {
		$vp = new \VisualPaginator($this, $name);
		$vp->getPaginator()->itemsPerPage = 10;
		return $vp;
	}

	protected function createTemplate($class=null) {
		$template = parent::createTemplate($class);
		$template->registerHelper('userLink', function($user) use ($template) {
			return Html::el('a', $user->username)->href($template->presenter->link('profile:show', $user->id));
		});
		return $template;
	}
}
