<?php

namespace App\Components;

use App;
use App\Helpers\Formatting\ChatFormatter;
use App\Model\Chat;
use Nette;
use Nette\Application\UI\Control;
use Nette\Application\Responses\TextResponse;
use Nextras\Forms\Rendering;


class ChatControl extends Control {
	/** @var App\Model\ChatRepository @inject */
	public $chats;

	/** @var App\Model\UserRepository @inject */
	public $users;

	/** @var App\Model\TelegramNotifier @inject */
	public $telegramNotifier;

	public function __construct(App\Model\ChatRepository $chats, App\Model\UserRepository $users, App\Model\TelegramNotifier $telegramNotifier) {
		$this->chats = $chats;
		$this->users = $users;
		$this->telegramNotifier = $telegramNotifier;
	}


	public function render() {
		$this->template->getLatte()->addFilter(null, [$this->presenter->context->getByType('App\Model\HelperLoader'), 'loader']);
		$this->template->setFile(__DIR__ . '/chat.latte');
		$allChats = $this->chats->findAll()->orderBy(['timestamp' => 'ASC']);
		$this->template->chats = $allChats->limitBy(51, max(0, $allChats->countStored() - 51));
		$this->template->activeUsers = $this->users->findActive();

		$this->template->render();
	}

	protected function createComponentChatForm() {
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

	public function handleRefresh($id) {
		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$this->template->getLatte()->addFilter(null, [$this->presenter->context->getByType('App\Model\HelperLoader'), 'loader']);
			$this->template->setFile(__DIR__ . '/chat-messages.latte');
			$this->template->chats = $this->chats->findBy(['id>=' => $id]);
			$this->presenter->sendResponse(new TextResponse($this->template));
		}
	}

	public function chatFormSucceeded(Nette\Application\UI\Form $form) {
		if (!$this->presenter->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->presenter->user->isAllowed('chat', 'send')) {
			$this->error('Pro odesílání do chatu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->values;

		$formatter = $this->presenter->context->getService('chatFormatter');

		$chat = new Chat;
		$chat->content = $formatter->format($values->content);
		$chat->ip = $this->presenter->context->getByType('Nette\Http\IRequest')->remoteAddress;
		$chat->user = $this->users->getById($this->presenter->user->identity->id);
		$this->chats->persistAndFlush($chat);
		try {
			$this->telegramNotifier->chatMessage($this->presenter->user->identity->username, trim(preg_replace('/\{#([0-9]+)\}/', '', $values->content)));
		} catch (Exception $e) {}
		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$this->presenter->sendResponse(new TextResponse('ok'));
		}
	}
}
