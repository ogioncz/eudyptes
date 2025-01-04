<?php

declare(strict_types=1);

namespace App\Components\Chat;

use App\Helpers\Formatting\ChatFormatter;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\Chat;
use App\Model\Orm\Chat\ChatRepository;
use App\Model\Orm\User\UserRepository;
use App\Model\TelegramNotifier;
use Exception;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

class ChatControl extends Control {
	public function __construct(
		private ChatRepository $chats,
		private UserRepository $users,
		private TelegramNotifier $telegramNotifier,
		private HelperLoader $helperLoader,
		private IRequest $request,
		private ChatFormatter $chatFormatter,
	) {
	}

	public function render(): void {
		$template = $this->getTemplate();
		$template->getLatte()->addFilterLoader([$this->helperLoader, 'loader']);
		$template->setFile(__DIR__ . '/chat.latte');
		$allChats = $this->chats->findAll()->orderBy(['timestamp' => 'ASC']);
		$template->chats = $allChats->limitBy(51, max(0, $allChats->countStored() - 51));
		$template->activeUsers = $this->users->findActive();

		$template->render();
	}

	protected function createComponentChatForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->addTextArea('content', 'Zpráva:')->setRequired();

		$submit = $form->addSubmit('send', 'Odeslat')->getControlPrototype();
		$submit->setName('button');
		$submit->addClass('chat-submit');
		$submit->create('span class="glyphicon glyphicon-send"');
		$submit->title = 'Odeslat';
		$form->onSuccess[] = [$this, 'chatFormSucceeded'];

		return $form;
	}

	public function handleRefresh($id): void {
		$presenter = $this->getPresenter();
		if (!$presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$template = $this->getTemplate();
			$template->getLatte()->addFilterLoader([$this->helperLoader, 'loader']);
			$template->setFile(__DIR__ . '/chat-messages.latte');
			$template->chats = $this->chats->findBy(['id>=' => $id]);
			$presenter->sendResponse(new TextResponse($template));
		}
	}

	public function chatFormSucceeded(Form $form): void {
		$presenter = $this->getPresenter();
		if (!$presenter->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $presenter->storeRequest()]);
		}
		if (!$presenter->getUser()->isAllowed('chat', 'send')) {
			$presenter->error('Pro odesílání do chatu musíš mít oprávnění.', IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();

		$formatter = $this->chatFormatter;

		$chat = new Chat();
		$chat->content = $formatter->format($values->content);
		$chat->ip = $this->request->getRemoteAddress();
		$chat->user = $this->users->getById($presenter->getUser()->getIdentity()->getId());
		$this->chats->persistAndFlush($chat);
		try {
			$username = $presenter->getUser()->getIdentity()->getData()['username'] ?? throw new Exception('Unexpected: missing username');
			$this->telegramNotifier->chatMessage($username, trim(preg_replace('/\{#([0-9]+)\}/', '', $values->content)));
		} catch (Exception) {
		}
		if (!$presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$presenter->sendResponse(new TextResponse('ok'));
		}
	}
}
