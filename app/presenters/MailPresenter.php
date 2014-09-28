<?php

namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;
use App;
use App\Model\Mail;

class MailPresenter extends BasePresenter {
	/** @var App\Model\Formatter @inject */
	public $formatter;

	/** @var App\Model\MailRepository @inject */
	public $mails;

	/** @var App\Model\UserRepository @inject */
	public $users;

	private $itemsPerPage = 25;

	public function renderList($sent = false) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$this->template->sent = $sent;
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemsPerPage = $this->itemsPerPage;
		$paginator->itemCount = $this->mails->findAll()->count();
		$this->template->mails = $this->mails->findBy([$sent ? 'sender' : 'recipient' => $this->user->identity->id])->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}

	public function renderShow($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$mail = $this->mails->getById($id);

		if (!$mail) {
			$this->error('Tato zpráva neexistuje');
		}

		if ($mail->sender->id !== $this->user->identity->id && $mail->recipient->id !== $this->user->identity->id) {
			$this->error('Toto není tvá zpráva', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		if (!$mail->read && $mail->recipient->id === $this->user->identity->id) {
			$mail->read = true;
			$this->mails->persistAndFlush($mail);
		}

		$this->template->mail = $mail;
	}

	protected function createComponentMailForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('subject', 'Předmět:')->setRequired()->getControlPrototype()->data['content'] = 'Předmět zprávy má výstižně charakerizovat, čeho se zpráva týká.';
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15);

		$form->addSubmit('send', 'Odeslat');
		$form->onSuccess[] = $this->mailFormSucceeded;

		return $form;
	}

	public function mailFormSucceeded(Nette\Application\UI\Form $form) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $form->values;
		$formatted = $this->formatter->format($values->markdown);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$mail = new Mail;
		$mail->subject = $values->subject;
		$mail->markdown = $values->markdown;
		$mail->content = $formatted['text'];
		$mail->sender = $this->users->getById($this->user->identity->id);
		$mail->ip = $this->context->httpRequest->remoteAddress;
		
		if ($this->action === 'reply') {
			$original_id = $this->getParameter('id');
			if (!$original_id) {
				$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
			}

			$original = $this->mails->getById($original_id);
			if (!$original) {
				$this->error('Zpráva s tímto id neexistuje.');
			}

			if ($original->recipient->id !== $this->user->identity->id) {
				$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', Nette\Http\IResponse::S403_FORBIDDEN);
			}
		
			$mail->recipient = $this->users->getById($original->sender);
			$mail->reaction = $this->mails->getById($original->id);
		} else {
			$recipient = $this->getParameter('recipient');

			if (!$recipient) {
				$this->error('Zadej id uživatele, kterému chceš napsat.');
			}

			$addressee = $this->users->getById($recipient);
			if (!$addressee) {
				$this->error('Uživatel s tímto id neexistuje.');
			}

			/** @TODO: blocking */

			$mail->recipient = $addressee;
		}

		$mail = $this->mails->persistAndFlush($mail);

		$this->flashMessage('Zpráva byla odeslána.', 'success');
		$this->redirect('show', $mail->id);
	}

	public function actionCreate($recipient) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if (!$recipient) {
			$this->error('Zadej id uživatele, kterému chceš napsat.');
		}

		$addressee = $this->users->getById($recipient);
		if (!$addressee) {
			$this->error('Uživatel s tímto id neexistuje.');
		}

		/** @TODO: blocking */
		$this->template->addressee = $addressee;
	}

	public function actionReply($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if (!$id) {
			$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
		}

		$original = $this->mails->getById($id);
		if (!$original) {
			$this->error('Zpráva s tímto id neexistuje.');
		}

		if ($original->recipient->id !== $this->user->identity->id) {
			$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$this->template->original = $original;
	}
}
