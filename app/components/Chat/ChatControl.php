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
		$this->template->chats = $allChats->limitBy(50, max(0, $allChats->countStored() - 50));
		$this->template->activeUsers = $this->users->findActive();

		$this->template->render();
	}

	protected function createComponentChatForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
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
			$this->redrawControl('chatActiveCount');
			$this->redrawControl('chatActiveList');
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

		$formatter = $this->presenter->context->getService('formatter');

		$chat = new Chat;
		$chat->content = $formatter->urlsToLinks(htmlSpecialChars($values->content));
		$chat->content = $formatter->replaceEmoticons($chat->content);
		$chat->content = preg_replace_callback('/\{#([0-9]+)\}/', function($m) {
			$original = $this->chats->getById($m[1]);
			if (!$original) {
				return '';
			}
			return <<<EOT
<blockquote>
<strong>{$this->presenter->createTemplate()->getLatte()->invokeFilter('userLink', [$original->user])}</strong>
{$original->content}
</blockquote>
EOT;
		}, $chat->content);
		$chat->ip = $this->presenter->context->getByType('Nette\Http\IRequest')->remoteAddress;
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
