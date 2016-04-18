<?php
namespace App\Presenters;

use App;

/**
 * RevisionPresenter displays and diffs revisions.
 */
class RevisionPresenter extends BasePresenter {
	/** @var App\Model\RevisionRepository @inject */
	public $revisions;

	public function renderShow($id) {
		$revision = $this->revisions->getById($id);
		if (!$revision) {
			$this->error('Revize nenalezena.');
		}
		$this->template->page = $revision->page;
		$this->template->revision = $revision;
	}

	public function renderDiff($id, $and, $type = 'side') {
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

		$this->template->page = $old->page;
		$this->template->diff = $diff;
		$this->template->old = $old;
		$this->template->new = $new;
	}

	public function renderList() {
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemsPerPage = 50;
		$revisions = $this->revisions->findAll();
		$paginator->itemCount = $revisions->countStored();
		$this->template->revisions = $revisions->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
