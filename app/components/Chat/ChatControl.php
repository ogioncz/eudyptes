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
		parent::__construct();
		$this->chats = $chats;
		$this->users = $users;
		$this->telegramNotifier = $telegramNotifier;
	}


	public function render() {
		$template = $this->getTemplate();
		$template->getLatte()->addFilter(null, [$this->getPresenter()->getContext()->getByType('App\Model\HelperLoader'), 'loader']);
		$template->setFile(__DIR__ . '/chat.latte');
		$allChats = $this->chats->findAll()->orderBy(['timestamp' => 'ASC']);
		$template->chats = $allChats->limitBy(51, max(0, $allChats->countStored() - 51));
		$template->activeUsers = $this->users->findActive();

		$template->render();
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
		$presenter = $this->getPresenter();
		if (!$presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$template = $this->getTemplate();
			$template->getLatte()->addFilter(null, [$presenter->getContext()->getByType('App\Model\HelperLoader'), 'loader']);
			$template->setFile(__DIR__ . '/chat-messages.latte');
			$template->chats = $this->chats->findBy(['id>=' => $id]);
			$presenter->sendResponse(new TextResponse($template));
		}
	}

	public function chatFormSucceeded(Nette\Application\UI\Form $form) {
		$presenter = $this->getPresenter();
		if (!$presenter->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $presenter->storeRequest()]);
		}
		if (!$presenter->getUser()->isAllowed('chat', 'send')) {
			$presenter->error('Pro odesílání do chatu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();

		$formatter = $presenter->getContext()->getService('chatFormatter');

		$chat = new Chat;
		$chat->content = $formatter->format($values->content);
		$chat->ip = $presenter->getContext()->getByType('Nette\Http\IRequest')->remoteAddress;
		$chat->user = $this->users->getById($presenter->getUser()->getIdentity()->id);
		$this->chats->persistAndFlush($chat);
		try {
			$this->telegramNotifier->chatMessage($presenter->getUser()->getIdentity()->username, trim(preg_replace('/\{#([0-9]+)\}/', '', $values->content)));
		} catch (\Exception $e) {}
		if (!$presenter->isAjax()) {
			$this->redirect('this');
		} else {
			$presenter->sendResponse(new TextResponse('ok'));
		}
	}
}
