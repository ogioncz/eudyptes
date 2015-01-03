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

	/** @var App\Model\ChatRepository @inject */
	public $chats;

	protected function createComponentPaginator($name) {
		$vp = new \VisualPaginator($this, $name);
		$vp->paginator->itemsPerPage = 10;
		return $vp;
	}

	protected function createTemplate($class=null) {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new App\Model\HelperLoader($this), 'loader']);
		return $template;
	}

	public function beforeRender() {
		parent::beforeRender();
		if ($this->user->loggedIn) {
			$user = $this->users->getById($this->user->identity->id);
			$this->template->unreadMails = $user->receivedMail->get()->findBy(['read' => false])->count();
			$this->template->upcomingMeetings = $this->meetings->findUpcoming()->count();
		}

		$this->template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$this->template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');

		$this->template->allowed = $this->allowed;
	}

	public function allowed($resource, $action) {
		$user = $this->user->loggedIn ? $this->users->getById($this->user->identity->id) : null;
		return $this->context->authorizator->isAllowed($user, $resource, $action);
	}

	/**
	* Chat control factory.
	* @return App\Components\ChatControl
	*/
	protected function createComponentChat() {
		$chat = new App\Components\ChatControl($this->chats, $this->users);
		return $chat;
	}
}
