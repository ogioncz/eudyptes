<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;
use App;

/**
 * BasePresenter is the mother of all presenters.
 */
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
		$vp->getPaginator()->itemsPerPage = 10;
		return $vp;
	}

	protected function startup() {
		if ($this->user->loggedIn) {
			$user = $this->users->getById($this->user->identity->id);
			$user->lastActivity = new DateTime();
			$this->users->persistAndFlush($user);
		}
		parent::startup();
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
			$this->template->unreadMails = $user->receivedMail->get()->findBy(['read' => false])->countStored();
			$this->template->upcomingMeetings = $this->meetings->findUpcoming()->countStored();
		}

		$this->template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$this->template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');


		$this->template->customStyles = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/custom'), function($f, $_k) {
			return $f->isFile() && $f->getExtension() == 'css';
		}), function($f, $_k) {
			return $this->template->basePath . '/custom/' . $f->getFileName();
		}));

		$this->template->customScripts = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/custom'), function($f, $_k) {
			return $f->isFile() && $f->getExtension() == 'js';
		}), function($f, $_k) {
			return $this->template->basePath . '/custom/' . $f->getFileName();
		}));

		$headers = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/images/header'), function($f, $_k) {
			return $f->isFile();
		}), function($f, $_k) {
			return $f->getFileName();
		}));
		shuffle($headers);

		$this->template->headerStyle = count($headers) > 0 ? 'background-image: url(' . $this->template->basePath . '/images/header/' . $headers[0] . ');' : '';

		$this->template->allowed = [$this, 'allowed'];
	}

	public function allowed($resource, $action) {
		$user = $this->user->loggedIn ? $this->users->getById($this->user->identity->id) : null;
		return $this->context->getService('authorizator')->isAllowed($user, $resource, $action);
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
