<?php
namespace App\Presenters;

use Nette;
use Caxy\HtmlDiff\HtmlDiff;

class RevisionPresenter extends BasePresenter {
	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderShow($id) {
		$revision = $this->database->table('page_revision')->get($id);
		if(!$revision) {
			$this->error('Revize nenalezena.');
		}
		$page = $revision->ref('page');
		$this->template->page = $page;
		$this->template->revision = $revision;
	}

	public function renderDiff($id, $and) {
		$old = $this->database->table('page_revision')->get($id);
		if(!$old) {
			$this->error('Revize nenalezena.');
		}
		$new = $this->database->table('page_revision')->get($and);
		if(!$new) {
			$this->error('Revize nenalezena.');
		}

		$differ = new HtmlDiff($old->content, $new->content);
		$differ->build();
		$diff = $differ->getDifference();

		$page = $old->ref('page');
		$this->template->page = $page;
		$this->template->diff = $diff;
		$this->template->old = $old;
		$this->template->new = $new;
	}
}
