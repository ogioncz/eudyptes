<?php

namespace App\Presenters;

use Nette;
use App;

class HomepagePresenter extends BasePresenter {
	/** @var App\Model\PostRepository @inject */
	public $posts;

	public function renderDefault() {
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemCount = $this->posts->findAll()->count();
		$this->template->posts = $this->posts->findAll()->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
