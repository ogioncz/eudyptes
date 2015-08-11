<?php

namespace App\Presenters;

use App;

class HomepagePresenter extends BasePresenter {
	/** @var App\Model\PostRepository @inject */
	public $posts;

	public function renderDefault() {
		$paginator = $this['paginator']->paginator;
		$paginator->itemCount = $this->posts->findAll()->countStored();
		$this->template->posts = $this->posts->findAll()->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
