<?php

namespace App\Components;

use App;
use App\Model\Chat;
use Nette;
use Nette\Application\UI\Control;
use Nextras\Forms\Rendering;


class ChatControl extends Control {
	/** @var App\Model\ChatRepository @inject */
	public $chats;

	/** @var App\Model\UserRepository @inject */
	public $users;

	public function __construct(App\Model\ChatRepository $chats, App\Model\UserRepository $users) {
		$this->chats = $chats;
		$this->users = $users;
	}


	public function render() {
		$this->template->getLatte()->addFilter(null, [new \App\Model\HelperLoader($this->presenter), 'loader']);
		$this->template->setFile(__DIR__ . '/chat.latte');
		$allChats = $this->chats->findAll()->orderBy(['timestamp' => 'ASC']);
		// if ($this->presenter->ajax && $this['chatForm']->success) {
		// 	$this->template->chats = $allChats->limitBy(50, max(0, $allChats->count() - 50));
		// // } else if (isset($this->presenter->params['do']) && $this->presenter->params['do'] === $this->name . '-refresh') {
		// // 	$this->template->chats = $this->chats->findBy(['id>' => $this->params['id']]);
		// } else {
		// 	$this->template->chats = $allChats->limitBy(50, max(0, $allChats->count() - 50));
		// }
		$this->template->chats = $allChats->limitBy(50, max(0, $allChats->countStored() - 50));

		$this->template->render();
	}

	protected function createComponentChatForm() {
		$form = new Nette\Application\UI\Form;
		$form->getElementPrototype()->class('ajax');
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addTextArea('content', 'Zpráva:')->setRequired();

		$form->addSubmit('send', 'Odeslat')->getControlPrototype();
		$form->onSuccess[] = $this->chatFormSucceeded;

		return $form;
	}

	public function handleRefresh($id) {
		$this->template->chats = $this->chats->findBy(['id>' => $id]);
		if (!$this->presenter->ajax) {
			$this->redirect('this');
		} else {
			$this->redrawControl('chatMessages');
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

		$chat = new Chat;
		$chat->content = $values->content;
		$chat->ip = $this->presenter->context->httpRequest->remoteAddress;
		$chat->user = $this->users->getById($this->presenter->user->identity->id);
		$this->chats->persistAndFlush($chat);
		if (!$this->presenter->ajax) {
			$this->redirect('this');
		} else {
			$this->redrawControl('chatMessages');
			$this->redrawControl('chatForm');
			$form->setValues([], TRUE);
		}
	}
}
