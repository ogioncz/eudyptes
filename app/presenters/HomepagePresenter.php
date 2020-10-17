<?php

namespace App\Presenters;

use App;

/**
 * HomepagePresenter handles the front page of the site.
 */
class HomepagePresenter extends BasePresenter {
	/** @var App\Model\PostRepository @inject */
	public $posts;

	public function renderDefault() {
		$paginator = $this['paginator']->getPaginator();
		$posts = $this->posts->findBy(['published' => true, 'createdAt<=' => new \DateTimeImmutable()]);
		$paginator->itemCount = $posts->countStored();
		$this->getTemplate()->posts = $posts->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
