<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nextras\Forms\Rendering;

class MailPresenter extends BasePresenter {
	/** @var \App\Model\Formatter @inject */
	public $formatter;

	/** @var Nette\Database\Context @inject */
	public $database;

	private $itemsPerPage = 25;

	public function renderList($sent = false) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$this->template->sent = $sent;
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemsPerPage = $this->itemsPerPage;
		$paginator->itemCount = $this->database->table('mail')->count('*');
		$this->template->mails = $this->database->table('mail')->where($sent ? 'from' : 'to', $this->user->identity->id)->order('timestamp DESC')->limit($paginator->itemsPerPage, $paginator->offset);
	}

	public function renderShow($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$mail = $this->database->table('mail')->get($id);

		if(!$mail) {
			$this->error('Tato zpráva neexistuje');
		}

		if($mail->ref('user', 'from')->id !== $this->user->identity->id && $mail->ref('user', 'to')->id !== $this->user->identity->id) {
			$this->error('Toto není tvá zpráva', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		if(!$mail->read && $mail->to === $this->user->identity->id) {
			$mail->update(['read' => true]);
		}

		$this->template->mail = $mail;
	}

	protected function createComponentMailForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('subject', 'Předmět:')->setRequired()->getControlPrototype()->data['content'] = 'Předmět zprávy má výstižně charakerizovat, čeho se zpráva týká.';
		$form->addTextArea('content', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15);

		$form->addSubmit('send', 'Odeslat');
		$form->onSuccess[] = $this->mailFormSucceeded;

		return $form;
	}
	
	public function mailFormSucceeded($form) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $form->getValues();
		$values['content'] = $this->formatter->format($values['content']);
		$values['from'] = $this->user->identity->id;
		$values['ip'] = $this->context->httpRequest->remoteAddress;
		
		if($this->getAction() === 'reply') {
			$original_id = $this->getParameter('id');
			if(!$original_id) {
				$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
			}

			$original = $this->database->table('mail')->get($original_id);
			if(!$original) {
				$this->error('Zpráva s tímto id neexistuje.');
			}

			if($original->to !== $this->user->identity->id) {
				$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', Nette\Http\IResponse::S403_FORBIDDEN);
			}
		
			$values['to'] = $original->from;
			$values['reaction'] = $original->id;
		} else {
			$to = $this->getParameter('to');

			if(!$to) {
				$this->error('Zadej id uživatele, kterému chceš napsat.');
			}

			$addressee = $this->database->table('user')->get($to);
			if(!$addressee) {
				$this->error('Uživatel s tímto id neexistuje.');
			}

			/** @TODO: blocking */

			$values['to'] = $to;
		}

		$mail = $this->database->table('mail')->insert($values);

		$this->flashMessage('Zpráva byla odeslána.', 'success');
		$this->redirect('show', $mail->id);
	}

	public function actionCreate($to) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if(!$to) {
			$this->error('Zadej id uživatele, kterému chceš napsat.');
		}

		$addressee = $this->database->table('user')->get($to);
		if(!$addressee) {
			$this->error('Uživatel s tímto id neexistuje.');
		}

		/** @TODO: blocking */
		$this->template->addressee = $addressee;
	}

	public function actionReply($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if(!$id) {
			$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
		}

		$original = $this->database->table('mail')->get($id);
		if(!$original) {
			$this->error('Zpráva s tímto id neexistuje.');
		}

		if($original->to !== $this->user->identity->id) {
			$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$this->template->original = $original;
	}
}
