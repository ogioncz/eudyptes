<?php

namespace App\Presenters;

use App;

class HomepagePresenter extends BasePresenter {
	/** @var App\Model\PostRepository @inject */
	public $posts;

	public function renderDefault() {
		$paginator = $this['paginator']->paginator;
		$posts = $this->posts->findBy(['published' => true, 'createdAt<=' => new \DateTime()]);
		$paginator->itemCount = $posts->countStored();
		$this->template->posts = $posts->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
