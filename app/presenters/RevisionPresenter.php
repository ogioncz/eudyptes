<?php
namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;

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
}
