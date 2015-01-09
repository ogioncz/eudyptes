<?php

namespace App\Presenters;

use Nette;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
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
		$paginator->itemCount = $this->mails->findBy([$sent ? 'sender' : 'recipient' => $this->user->identity->id])->countStored();
		$this->template->mails = $this->mails->findBy([$sent ? 'sender' : 'recipient' => $this->user->identity->id])->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}

	public function renderShow($id, $tree = false) {
		if ($tree) {
			$this->template->setFile(__DIR__ . '/../templates/Mail/tree.latte');
		}

		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$mail = $this->mails->getById($id);

		if (!$mail) {
			$this->error('Tato zpráva neexistuje');
		}

		if (!$this->allowed($mail, 'show')) {
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

		$subject = $form->addText('subject', 'Předmět:')->setRequired();
		$subject->getControlPrototype()->data['content'] = 'Předmět zprávy má výstižně charakerizovat, čeho se zpráva týká.';
		if ($this->action === 'reply') {
			$subject->setDefaultValue('re: ' . preg_replace('/^(?:re: )+/i', '', $this->template->original->subject));
		}

		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

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
		$mail->ip = $this->context->getByType('Nette\Http\IRequest')->remoteAddress;

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

		if ($mail->recipient->notifyByMail) {
			$this->notifyByMail($mail);
		}

		$this->flashMessage('Zpráva byla odeslána.', 'success');
		$this->redirect('show', $mail->id);
	}

	protected function notifyByMail(Mail $mail) {
		$messageTemplate = $this->createTemplate();
		$messageTemplate->sentMail = $mail;
		$messageTemplate->sender = $mail->sender->username;
		$messageTemplate->setFile($this->context->parameters['appDir'] . '/templates/Mail/@notification.latte');

		$message = new Message;
		$message->setFrom($messageTemplate->sender . ' <neodpovidat@fan-club-penguin.cz>');
		$message->subject = 'Nová zpráva ' . $mail->subject . ' (fan-club-penguin.cz)';
		$message->addTo($mail->recipient->email);
		$message->setBody($messageTemplate);

		$mailer = new SendmailMailer;
		$mailer->send($message);
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
