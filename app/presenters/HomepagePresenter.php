<?php

namespace App\Presenters;

use App;

class HomepagePresenter extends BasePresenter {
	/** @var App\Model\PostRepository @inject */
	public $posts;

	public function renderDefault() {
		$paginator = $this['paginator']->paginator;
		$paginator->itemCount = $this->posts->findBy(['published' => true])->countStored();
		$this->template->posts = $this->posts->findBy(['published' => true])->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
