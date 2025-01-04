<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\Orm\Post\PostRepository;
use DateTimeImmutable;
use Nette\DI\Attributes\Inject;

/**
 * HomepagePresenter handles the front page of the site.
 */
class HomepagePresenter extends BasePresenter {
	#[Inject]
	public PostRepository $posts;

	public function renderDefault(): void {
		$paginator = $this['paginator']->getPaginator();
		$posts = $this->posts->findBy(['published' => true, 'createdAt<=' => new DateTimeImmutable()]);
		$paginator->itemCount = $posts->countStored();
		$this->getTemplate()->posts = $posts->orderBy(['createdAt' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
