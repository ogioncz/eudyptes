<?php

declare(strict_types=1);

namespace App\Components;

use App;
use App\Model\Chat;
use Nette;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Control;

class ChatControl extends Control {
	public function __construct(
		private App\Model\ChatRepository $chats,
		private App\Model\UserRepository $users,
		private App\Model\TelegramNotifier $telegramNotifier,
		private App\Model\HelperLoader $helperLoader,
		private Nette\Http\IRequest $request,
		private App\Helpers\Formatting\ChatFormatter $chatFormatter,
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

	protected function createComponentChatForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form;
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

	public function chatFormSucceeded(Nette\Application\UI\Form $form): void {
		$presenter = $this->getPresenter();
		if (!$presenter->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $presenter->storeRequest()]);
		}
		if (!$presenter->getUser()->isAllowed('chat', 'send')) {
			$presenter->error('Pro odesílání do chatu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();

		$formatter = $this->chatFormatter;

		$chat = new Chat;
		$chat->content = $formatter->format($values->content);
		$chat->ip = $this->request->remoteAddress;
		$chat->user = $this->users->getById($presenter->getUser()->getIdentity()->id);
		$this->chats->persistAndFlush($chat);
		try {
			$this->telegramNotifier->chatMessage($presenter->getUser()->getIdentity()->username, trim(preg_replace('/\{#([0-9]+)\}/', '', $values->content)));
		} catch (\Exception) {
		}
		if (!$presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$presenter->sendResponse(new TextResponse('ok'));
		}
	}
}
