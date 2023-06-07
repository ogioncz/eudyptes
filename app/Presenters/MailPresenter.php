<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Helpers\Formatting\Formatter;
use App\Model\Orm\Mail\Mail;
use App\Model\Orm\Mail\MailRepository;
use App\Model\Orm\User\UserRepository;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\IResponse;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;

/**
 * MailPresenter handles messages sent between users.
 */
class MailPresenter extends BasePresenter {
	#[Inject]
	public Container $context;

	#[Inject]
	public Formatter $formatter;

	#[Inject]
	public MailRepository $mails;

	#[Inject]
	public UserRepository $users;

	private int $itemsPerPage = 25;

	public function renderList($sent = false): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$template = $this->getTemplate();
		$template->sent = $sent;
		$paginator = $this['paginator']->getPaginator();
		$paginator->itemsPerPage = $this->itemsPerPage;
		$mails = $this->mails->findBy([$sent ? 'sender' : 'recipient' => $this->getUser()->getIdentity()->getId()]);
		$paginator->itemCount = $mails->countStored();
		$template->mails = $mails->orderBy(['timestamp' => 'DESC'])->limitBy($paginator->itemsPerPage, $paginator->offset);
	}

	public function actionShow($id, $tree = false): void {
		$template = $this->getTemplate();

		if ($tree) {
			$template->setFile(__DIR__ . '/../templates/Mail/tree.latte');
		}

		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$mail = $this->mails->getById($id);

		if (!$mail) {
			$this->error('Tato zpráva neexistuje');
		}

		if (!$this->allowed($mail, 'show')) {
			$this->error('Toto není tvá zpráva', IResponse::S403_FORBIDDEN);
		}

		if (!$mail->read && $mail->recipient->id === $this->getUser()->getIdentity()->getId()) {
			$mail->read = true;
			$this->mails->persistAndFlush($mail);
		}

		$template->mail = $mail;
	}

	protected function createComponentMailForm(): Form {
		$form = new Form();
		$form->addProtection();
		$renderer = new Bs3FormRenderer();
		$form->setRenderer($renderer);

		$subject = $form->addText('subject', 'Předmět:')->setRequired();
		$subject->getControlPrototype()->autofocus = true;
		$subject->getControlPrototype()->addClass('mail-subject')->data('content', 'Předmět zprávy má výstižně charakterizovat, čeho se zpráva týká.');
		if ($this->getAction() === 'reply') {
			$subject->setDefaultValue('re: ' . preg_replace('/^(?:re: )+/i', '', $this->getTemplate()->original->subject));
		}

		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = [$this, 'mailFormPreview'];
		$previewButton->getControlPrototype()->addClass('ajax');

		$submitButton = $form->addSubmit('send', 'Odeslat');
		$submitButton->onClick[] = [$this, 'mailFormSucceeded'];
		$renderer->primaryButton = $submitButton;

		return $form;
	}

	public function mailFormSucceeded(SubmitButton $button): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();
		$formatted = $this->formatter->format($values->markdown);

		if (is_countable($formatted['errors']) ? \count($formatted['errors']) : 0) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$mail = new Mail();
		$mail->subject = $values->subject;
		$mail->markdown = $values->markdown;
		$mail->content = $formatted['text'];
		$mail->sender = $this->users->getById($this->getUser()->getIdentity()->getId());
		$mail->ip = $this->getHttpRequest()->getRemoteAddress()();

		if ($this->getAction() === 'reply') {
			$original_id = $this->getParameter('id');
			if (!$original_id) {
				$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
			}

			$original = $this->mails->getById($original_id);
			if (!$original) {
				$this->error('Zpráva s tímto id neexistuje.');
			}

			if ($original->recipient->id !== $this->getUser()->getIdentity()->getId()) {
				$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', IResponse::S403_FORBIDDEN);
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

			$mail->recipient = $addressee;
		}

		if (!$this->allowed($mail->recipient, 'sendMail')) {
			$this->error('Tomuto uživateli nemůžeš poslat zprávu.');
		}

		$mail = $this->mails->persistAndFlush($mail);

		if ($mail->recipient->notifyByMail) {
			$this->notifyByMail($mail);
		}

		$this->flashMessage('Zpráva byla odeslána.', 'success');
		$this->redirect('show', $mail->id);
	}

	public function mailFormPreview(SubmitButton $button): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		$formatted = $this->formatter->format($values['markdown']);

		if (is_countable($formatted['errors']) ? \count($formatted['errors']) : 0) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->getTemplate()->preview = $formatted['text'];

		$this->flashMessage('Toto je jen náhled, zpráva zatím nebyla uložena.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	protected function notifyByMail(Mail $mail): void {
		$messageTemplate = $this->createTemplate();
		$messageTemplate->sentMail = $mail;
		$messageTemplate->sender = $mail->sender->username;
		$messageTemplate->setFile($this->context->parameters['appDir'] . '/templates/Mail/@notification.latte');

		$message = new Message();
		$message->setFrom($messageTemplate->sender . ' <neodpovidat@fan-club-penguin.cz>');
		$message->setSubject('Nová zpráva ' . $mail->subject . ' (fan-club-penguin.cz)');
		$message->addTo($mail->recipient->email);
		$message->setBody((string) $messageTemplate);

		$mailer = new SendmailMailer();
		$mailer->send($message);
	}

	public function actionCreate($recipient): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if (!$recipient) {
			$this->error('Zadej id uživatele, kterému chceš napsat.');
		}

		$addressee = $this->users->getById($recipient);
		if (!$addressee) {
			$this->error('Uživatel s tímto id neexistuje.');
		}

		if (!$this->allowed($addressee, 'sendMail')) {
			$this->error('Tomuto uživateli nemůžeš poslat zprávu.');
		}

		$this->getTemplate()->addressee = $addressee;
	}

	public function actionReply($id): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if (!$id) {
			$this->error('Zadej id zprávy, na kterou chceš odpovědět.');
		}

		$original = $this->mails->getById($id);
		if (!$original) {
			$this->error('Zpráva s tímto id neexistuje.');
		}

		if ($original->recipient->id !== $this->getUser()->getIdentity()->getId()) {
			$this->error('Zpráva, na kterou chceš odpovědět není určena do tvých rukou.', IResponse::S403_FORBIDDEN);
		}
		$this->getTemplate()->original = $original;
	}
}
