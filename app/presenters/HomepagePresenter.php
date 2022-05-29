<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;

/**
 * HomepagePresenter handles the front page of the site.
 */
class HomepagePresenter extends BasePresenter {
	#[Nette\DI\Attributes\Inject]
	public App\Model\PostRepository $posts;

	public function renderDefault(): void {
		$paginator = $this['paginator']->getPaginator();
		$posts = $this->posts->findBy(['published' => true, 'createdAt<=' => new \DateTimeImmutable()]);
		$paginator->itemCount = $posts->countStored();
		$this->getTemplate()->posts = $posts->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
