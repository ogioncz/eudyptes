<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use Nette;

/**
 * RevisionPresenter displays and diffs revisions.
 */
class RevisionPresenter extends BasePresenter {
	#[Nette\DI\Attributes\Inject]
	public App\Model\RevisionRepository $revisions;

	public function renderShow($id): void {
		$revision = $this->revisions->getById($id);
		if (!$revision) {
			$this->error('Revize nenalezena.');
		}
		$template = $this->getTemplate();
		$template->page = $revision->page;
		$template->revision = $revision;
	}

	public function renderDiff($id, $and, $type = 'side'): void {
		$old = $this->revisions->getById($id);
		if (!$old) {
			$this->error('Revize nenalezena.');
		}
		$new = $this->revisions->getById($and);
		if (!$new) {
			$this->error('Revize nenalezena.');
		}

		$differ = new \Actinarium\Diff\Diff(explode("\n", $old->markdown), explode("\n", $new->markdown));
		if ($type === 'inline') {
			$renderer = new \Actinarium\Diff\Renderer\Html\InlineRenderer;
		} else {
			$renderer = new \Actinarium\Diff\Renderer\Html\SideBySideRenderer;
		}

		$diff = $differ->render($renderer);

		$template = $this->getTemplate();
		$template->page = $old->page;
		$template->diff = $diff;
		$template->old = $old;
		$template->new = $new;
	}

	public function renderList(): void {
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemsPerPage = 50;
		$revisions = $this->revisions->findAll();
		$paginator->itemCount = $revisions->countStored();
		$template = $this->getTemplate();
		$template->revisions = $revisions->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
