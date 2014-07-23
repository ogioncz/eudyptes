<?php

namespace App\Presenters;

use Nette, App, Nextras\Forms\Rendering;


/**
 * Meeting presenter.
 */
class MeetingPresenter extends BasePresenter {
	/** @var \Parsedown @inject */
	public $parsedown;
	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderList($sent = false) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$this->template->meetings = $this->database->table('meeting')->where('date >= CURDATE()')->order('date');	
	}

	/**
	* Participator control factory.
	* @return FifteenControl
	*/
	protected function createComponentParticipator() {
		$participator = new App\Components\Participator;
		return $participator;
	}
}
