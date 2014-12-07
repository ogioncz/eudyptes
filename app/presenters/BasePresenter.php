<?php

namespace App\Presenters;

use Nette;
use App;

abstract class BasePresenter extends Nette\Application\UI\Presenter {
	/** @var App\Model\UserRepository @inject */
	public $users;

	/** @var App\Model\MeetingRepository @inject */
	public $meetings;

	/** @var App\Model\PageRepository @inject */
	public $pages;

	public function startup() {
		parent::startup();
		if(isset($this->formatter)) {
			$this->formatter->parsedown->presenter = $this;
		}
	}

	protected function createComponentPaginator($name) {
		$vp = new \VisualPaginator($this, $name);
		$vp->getPaginator()->itemsPerPage = 10;
		return $vp;
	}

	protected function createTemplate($class=null) {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new App\Model\HelperLoader($this), 'loader']);
		return $template;
	}

	public function beforeRender() {
		parent::beforeRender();
		if($this->user->loggedIn) {
			$user = $this->users->getById($this->user->identity->id);
			$this->template->unreadMails = $user->receivedMail->get()->findBy(['read' => false])->count();
			$this->template->upcomingMeetings = $this->meetings->findUpcoming()->count();
		}

		$this->template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$this->template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');
	}
}
