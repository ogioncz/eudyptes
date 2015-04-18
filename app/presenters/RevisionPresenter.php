<?php
namespace App\Presenters;

use App;

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

		$differ = new \Diff(explode("\n", $old->markdown), explode("\n", $new->markdown));
		if ($type === 'inline') {
			$renderer = new \Diff_Renderer_Html_Inline;
		} else {
			$renderer = new \Diff_Renderer_Html_SideBySide;
		}

		$diff = $differ->render($renderer);

		$this->template->page = $old->page;
		$this->template->diff = $diff;
		$this->template->old = $old;
		$this->template->new = $new;
	}

	public function renderList() {
		$paginator = $this['paginator']->paginator;
		$paginator->itemsPerPage = 50;
		$paginator->itemCount = $this->revisions->findAll()->countStored();
		$this->template->revisions = $this->revisions->findAll()->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
