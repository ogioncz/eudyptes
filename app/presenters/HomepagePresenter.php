<?php

namespace App\Presenters;

use Nette;

class HomepagePresenter extends BasePresenter {
	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderDefault() {
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemCount = $this->database->table('post')->count('*');
		$this->template->posts = $this->database->table('post')->order('timestamp DESC')->limit($paginator->itemsPerPage, $paginator->offset);
	}
}
