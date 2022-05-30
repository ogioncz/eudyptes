<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use DateTimeImmutable;
use Nette;

/**
 * BasePresenter is the mother of all presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	#[Nette\DI\Attributes\Inject]
	public Nette\Security\Permission $authorizator;

	#[Nette\DI\Attributes\Inject]
	public App\Model\UserRepository $users;

	#[Nette\DI\Attributes\Inject]
	public App\Model\MeetingRepository $meetings;

	#[Nette\DI\Attributes\Inject]
	public App\Model\PageRepository $pages;

	#[Nette\DI\Attributes\Inject]
	public App\Model\ChatRepository $chats;

	#[Nette\DI\Attributes\Inject]
	public App\Model\TelegramNotifier $telegramNotifier;

	#[Nette\DI\Attributes\Inject]
	public App\Helpers\Formatting\ChatFormatter $chatFormatter;

	public function __construct(private \App\Model\HelperLoader $helperLoader) {
		parent::__construct();
	}

	protected function createComponentPaginator($name): \VisualPaginator {
		$vp = new \VisualPaginator();
		$vp->getPaginator()->setItemsPerPage(10);
		$this->addComponent($vp, $name);

		return $vp;
	}

	protected function startup(): void {
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->getId());
			$user->lastActivity = new DateTimeImmutable();
			$this->users->persistAndFlush($user);
		}
		parent::startup();
	}

	protected function createTemplate($class = null): Nette\Application\UI\Template {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilterLoader([$this->helperLoader, 'loader']);

		return $template;
	}

	public function beforeRender(): void {
		parent::beforeRender();
		$template = $this->getTemplate();
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->getId());
			$template->unreadMails = $user->receivedMail->get()->findBy(['read' => false])->countStored();
			$template->upcomingMeetings = $this->meetings->findUpcoming()->countStored();
		}

		$template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');

		$template->customStyles = iterator_to_array(
			new Nette\Iterators\Mapper(
				new \CallbackFilterIterator(
					new \DirectoryIterator(__DIR__ . '/../../www/custom'),
					fn($f, $_k) => $f->isFile() && $f->getExtension() == 'css'
				),
				fn($f, $_k) => $template->basePath . '/custom/' . $f->getFileName()
			)
		);

		$template->customScripts = iterator_to_array(
			new Nette\Iterators\Mapper(
				new \CallbackFilterIterator(
					new \DirectoryIterator(__DIR__ . '/../../www/custom'),
					fn($f, $_k) => $f->isFile() && $f->getExtension() == 'js'
				),
				fn($f, $_k) => $template->basePath . '/custom/' . $f->getFileName()
			)
		);

		$headers = iterator_to_array(
			new Nette\Iterators\Mapper(
				new \CallbackFilterIterator(
					new \DirectoryIterator(__DIR__ . '/../../www/images/header'),
					fn($f, $_k) => $f->isFile() && \in_array($f->getExtension(), ['png', 'jpg', 'jpeg', 'gif'], true)
				),
				fn($f, $_k) => $f->getFileName()
			)
		);
		shuffle($headers);

		$template->headerStyle = \count($headers) > 0 ? 'background-image: url(' . $template->basePath . '/images/header/' . $headers[0] . ');' : '';

		$template->allowed = [$this, 'allowed'];
	}

	public function allowed($resource, $action): bool {
		$user = $this->getUser()->isLoggedIn() ? $this->users->getById($this->getUser()->getIdentity()->getId()) : null;

		return $this->authorizator->isAllowed($user, $resource, $action);
	}

	/**
	 * Chat control factory.
	 */
	protected function createComponentChat(): \App\Components\ChatControl {
		$chat = new App\Components\ChatControl(
			$this->chats,
			$this->users,
			$this->telegramNotifier,
			$this->helperLoader,
			$this->getHttpRequest(),
			$this->chatFormatter,
		);

		return $chat;
	}
}
