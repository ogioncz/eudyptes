<?php

namespace App\Presenters;

use Nette;
use DateTimeImmutable;
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

	/** @var App\Model\TelegramNotifier @inject */
	public $telegramNotifier;

	protected function createComponentPaginator($name) {
		$vp = new \VisualPaginator();
		$vp->getPaginator()->setItemsPerPage(10);
		$this->addComponent($vp, $name);
		return $vp;
	}

	protected function startup() {
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->id);
			$user->lastActivity = new DateTimeImmutable();
			$this->users->persistAndFlush($user);
		}
		parent::startup();
	}

	protected function createTemplate($class=null) {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [$this->getContext()->getByType('App\Model\HelperLoader'), 'loader']);
		return $template;
	}

	public function beforeRender() {
		parent::beforeRender();
		$template = $this->getTemplate();
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->id);
			$template->unreadMails = $user->receivedMail->get()->findBy(['read' => false])->countStored();
			$template->upcomingMeetings = $this->meetings->findUpcoming()->countStored();
		}

		$template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');


		$template->customStyles = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/custom'), function($f, $_k) {
			return $f->isFile() && $f->getExtension() == 'css';
		}), function($f, $_k) use ($template) {
			return $template->basePath . '/custom/' . $f->getFileName();
		}));

		$template->customScripts = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/custom'), function($f, $_k) {
			return $f->isFile() && $f->getExtension() == 'js';
		}), function($f, $_k) use ($template) {
			return $template->basePath . '/custom/' . $f->getFileName();
		}));

		$headers = iterator_to_array(new Nette\Iterators\Mapper(new \CallbackFilterIterator(new \DirectoryIterator(__DIR__ . '/../../www/images/header'), function($f, $_k) {
			return $f->isFile() && in_array($f->getExtension(), ['png', 'jpg', 'jpeg', 'gif'], true);
		}), function($f, $_k) {
			return $f->getFileName();
		}));
		shuffle($headers);

		$template->headerStyle = count($headers) > 0 ? 'background-image: url(' . $template->basePath . '/images/header/' . $headers[0] . ');' : '';

		$template->allowed = [$this, 'allowed'];
	}

	public function allowed($resource, $action) {
		$user = $this->getUser()->isLoggedIn() ? $this->users->getById($this->getUser()->getIdentity()->id) : null;
		return $this->getContext()->getService('authorizator')->isAllowed($user, $resource, $action);
	}

	/**
	* Chat control factory.
	* @return App\Components\ChatControl
	*/
	protected function createComponentChat() {
		$chat = new App\Components\ChatControl($this->chats, $this->users, $this->telegramNotifier);
		return $chat;
	}
}
