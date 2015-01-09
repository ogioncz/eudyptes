<?php
namespace App\Presenters;

use Caxy\HtmlDiff\HtmlDiff;
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

	public function renderDiff($id, $and) {
		$old = $this->revisions->getById($id);
		if (!$old) {
			$this->error('Revize nenalezena.');
		}
		$new = $this->revisions->getById($and);
		if (!$new) {
			$this->error('Revize nenalezena.');
		}

		$differ = new HtmlDiff($old->content, $new->content);
		$differ->build();
		$diff = $differ->getDifference();

		$this->template->page = $old->page;
		$this->template->diff = $diff;
		$this->template->old = $old;
		$this->template->new = $new;
	}

	public function renderList() {
		$paginator = $this['paginator']->paginator;
		$paginator->itemsPerPage = 50;
		$paginator->itemCount = $this->revisions->findAll()->count();
		$this->template->revisions = $this->revisions->findAll()->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}
}
