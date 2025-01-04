<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\Chat\ChatControl;
use App\Helpers\Formatting\ChatFormatter;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use App\Model\Orm\Meeting\MeetingRepository;
use App\Model\Orm\Page\PageRepository;
use App\Model\Orm\User\UserRepository;
use App\Model\TelegramNotifier;
use CallbackFilterIterator;
use DateTimeImmutable;
use DirectoryIterator;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\DI\Attributes\Inject;
use Nette\Iterators\Mapper;
use Nette\Security\Permission;
use Override;
use VisualPaginator;

/**
 * BasePresenter is the mother of all presenters.
 */
abstract class BasePresenter extends Presenter {
	#[Inject]
	public Permission $authorizator;

	#[Inject]
	public UserRepository $users;

	#[Inject]
	public MeetingRepository $meetings;

	#[Inject]
	public PageRepository $pages;

	#[Inject]
	public ChatRepository $chats;

	#[Inject]
	public TelegramNotifier $telegramNotifier;

	#[Inject]
	public ChatFormatter $chatFormatter;

	public function __construct(private readonly HelperLoader $helperLoader) {
		parent::__construct();
	}

	protected function createComponentPaginator(?string $name): VisualPaginator {
		$vp = new VisualPaginator();
		$vp->getPaginator()->setItemsPerPage(10);
		$this->addComponent($vp, $name);

		return $vp;
	}

	#[Override]
	protected function startup(): void {
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->getId());
			$user->lastActivity = new DateTimeImmutable();
			$this->users->persistAndFlush($user);
		}
		parent::startup();
	}

	#[Override]
	protected function createTemplate($class = null): Template {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilterLoader($this->helperLoader->loader(...));

		return $template;
	}

	#[Override]
	public function beforeRender(): void {
		parent::beforeRender();
		$template = $this->getTemplate();
		if ($this->getUser()->isLoggedIn()) {
			$user = $this->users->getById($this->getUser()->getIdentity()->getId());
			$template->unreadMails = $user->receivedMail->toCollection()->findBy(['read' => false])->countStored();
			$template->upcomingMeetings = $this->meetings->findUpcoming()->countStored();
		}

		$template->menu = $this->pages->findBy(['menu' => true])->orderBy(['title' => 'ASC']);
		$template->logo = file_get_contents(__DIR__ . '/../../www/images/bar.svg');

		$template->customStyles = iterator_to_array(
			new Mapper(
				new CallbackFilterIterator(
					new DirectoryIterator(__DIR__ . '/../../www/custom'),
					fn($f, $_k): bool => $f->isFile() && $f->getExtension() == 'css'
				),
				fn($f, $_k): string => $template->basePath . '/custom/' . $f->getFileName()
			)
		);

		$template->customScripts = iterator_to_array(
			new Mapper(
				new CallbackFilterIterator(
					new DirectoryIterator(__DIR__ . '/../../www/custom'),
					fn($f, $_k): bool => $f->isFile() && $f->getExtension() == 'js'
				),
				fn($f, $_k): string => $template->basePath . '/custom/' . $f->getFileName()
			)
		);

		$headers = iterator_to_array(
			new Mapper(
				new CallbackFilterIterator(
					new DirectoryIterator(__DIR__ . '/../../www/images/header'),
					fn($f, $_k): bool => $f->isFile() && \in_array($f->getExtension(), ['png', 'jpg', 'jpeg', 'gif'], true)
				),
				fn($f, $_k) => $f->getFileName()
			)
		);
		shuffle($headers);

		$template->headerStyle = \count($headers) > 0 ? 'background-image: url(' . $template->basePath . '/images/header/' . $headers[0] . ');' : '';

		$template->allowed = $this->allowed(...);
	}

	public function allowed($resource, $action): bool {
		$user = $this->getUser()->isLoggedIn() ? $this->users->getById($this->getUser()->getIdentity()->getId()) : null;

		return $this->authorizator->isAllowed($user, $resource, $action);
	}

	/**
	 * Chat control factory.
	 */
	protected function createComponentChat(): ChatControl {
		$chat = new ChatControl(
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
