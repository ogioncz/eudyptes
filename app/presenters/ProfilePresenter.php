<?php

namespace App\Presenters;

use App;
use App\Model\Token;
use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Database\UniqueConstraintViolationException;
use Tracy\Debugger;
use Nextras\Forms\Rendering;
use Exception;

class ProfilePresenter extends BasePresenter {
	/** @var App\Model\UserRepository @inject */
	public $users;

	/** @var App\Model\TokenRepository @inject */
	public $tokens;

	/** @var App\Model\StampRepository @inject */
	public $stamps;

	/** @persistent */
	public $token = null;

	/** @persistent */
	public $tid = null;

	/** @var App\Model\User @persistent */
	public $profile = null;

	public function renderList() {
		$this->template->profiles = $this->users->findAll()->orderBy('username');
	}

	public function renderShow($id) {
		$this->profile = $this->users->getById($id);
		if (!$this->profile) {
			$this->error('Uživatel nenalezen');
		}
		if (file_exists($this->context->parameters['avatarStorage'] . '/' . $this->profile->id . 'm.png')) {
			$this->template->avatar = str_replace('♥basePath♥', $this->context->getByType('Nette\Http\IRequest')->url->baseUrl, $this->context->parameters['avatarStoragePublic']) . '/' . $this->profile->id . 'm.png';
		}

		$this->template->ipAddress = $this->context->getByType('Nette\Http\IRequest')->remoteAddress;
		$this->template->profile = $this->profile;
		$this->template->stamps = $this->stamps->findAll();
	}

	public function actionEdit($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$this->profile = $this->users->getById($id);
		if (!$this->profile) {
			$this->error('Uživatel nenalezen');
		}
		if (!$this->allowed($this->profile, 'edit')) {
			$this->error('Nemáš oprávnění upravovat tohoto uživatele.');
		}

		$data = $this->profile->toArray();
		$this['profileForm']->setDefaults($data);
	}

	protected function createComponentProfileForm() {
		$form = new Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$username = $form->addText('username', 'Přezdívka:');

		if (!$this->allowed($this->profile, 'rename')) {
			$username->disabled = true;
		}

		$form->addUpload('avatar', 'Avatar:')->addCondition(Form::FILLED)->addRule(Form::MIME_TYPE, 'Nahraj prosím obrázek ve formátu PNG.', ['image/png']);

		$medium = $this->context->parameters['avatarStorage'] . '/' . $this->profile->id . 'm.png';
		if (file_exists($medium)) {
			$form->addCheckbox('removeAvatar', 'Odstranit avatar');
		}

		$email = $form->addText('email', 'E-Mail:')->setType('email');
		$email->setOption('description', 'Slouží k upozorňování na zprávy a obnovu hesla. Bez tvého souhlasu ti nebudeme nic posílat.');
		$email->setRequired('Zadej prosím svůj e-mail.');
		$email->addRule($form::EMAIL, 'Zadej prosím platný e-mail.');

		$form->addText('skype', 'Skype:');

		$form->addRadioList('member', 'Member:', [(string) true => 'Ano', (string) false => 'Ne']);

		$form->addCheckbox('notifyByMail', 'Zasílat oznámení o nové zprávě na e-mail');

		$password = $form->addPassword('password', 'Heslo:');
		$password->setOption('description', 'Pokud chceš změnit heslo, zadej nové.');

		$form->addSubmit('send', 'Uložit změny');

		$form->onSuccess[] = [$this, 'profileFormSucceeded'];
		return $form;
	}

	public function profileFormSucceeded(Form $form) {
		$values = $form->values;
		if (!$this->allowed($this->profile, 'edit')) {
			$this->error('Nemáš oprávnění upravovat tohoto uživatele.');
		}

		$user = $this->profile;

		if ($this->allowed($this->profile, 'rename')) {
			$user->username = $values->username;
		}

		$user->email = $values->email;
		$user->skype = $values->skype;
		$user->member = (bool) $values->member;
		$user->notifyByMail = $values->notifyByMail;

		$original = $this->context->parameters['avatarStorage'] . '/' . $user->id . '.png';
		$medium = $this->context->parameters['avatarStorage'] . '/' . $user->id . 'm.png';

		if (isset($values->removeAvatar) && $values->removeAvatar) {
			@unlink($original);
			@unlink($medium);
		}

		if ($values->avatar->isOk()) {
			$values->avatar->move($original);
			try {
				$resized = Image::fromFile($original)->resize(100, 100);
				Image::fromBlank('100', '100', Image::rgb(0, 0, 0, 127))->place($resized, '50%', '50%')->save($medium);
			} catch (Exception $e) {
				$form->addError('Chyba při zpracování avataru.');
				Debugger::log($e);
			}
		}

		if ($values->password) {
			$user->password = Passwords::hash($values->password);
		}

		try {
			$this->users->persistAndFlush($user);
			$this->flashMessage('Profil byl úspěšně upraven.', 'success');
			$this->redirect('show', $user->id);
		} catch (UniqueConstraintViolationException $e) {
			$form->addError($this->allowed($user, 'rename') ? 'Tento e-mail nebo přezdívka jsou již obsazeny.' : 'Tento e-mail je již obsazen.');
		} catch (PDOException $e) {
			$file = Debugger::log($e);
			$form->addError('Nastala neznámá chyba. Informace o chybě byly uloženy do souboru ' . basename($file));
		}
	}

	protected function createComponentSignUpForm() {
		$form = new Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$username = $form->addText('username', 'Přezdívka:');
		$username->getControlPrototype()->autofocus = true;
		$username->setRequired('Zadej prosím své uživatelské jméno.');
		$username->setOption('description', 'Pod tímto jménem tě budou znát ostatní uživatelé.');

		$email = $form->addText('email', 'E-Mail:')->setType('email');
		$email->setOption('description', 'Slouží k upozorňování na zprávy a obnovu hesla. Bez tvého souhlasu ti nebudeme nic posílat.');
		$email->setRequired('Zadej prosím svůj e-mail.');
		$email->addRule($form::EMAIL, 'Zadej prosím platný e-mail.');

		$form->addPassword('password', 'Heslo:')->setRequired('Zadej prosím své heslo.');

		$noSpam = $form->addText('nospam', 'Zadej „nospam“');
		$noSpam->addRule(Form::FILLED, 'Ošklivý spamovací robote!');
		$noSpam->addRule(Form::EQUAL, 'Ošklivý spamovací robote!', 'nospam');
		$noSpam->getLabelPrototype()->class('nospam');
		$noSpam->getControlPrototype()->class('nospam');

		$form->addSubmit('send', 'Zaregistrovat se');

		$form->onSuccess[] = [$this, 'signUpFormSucceeded'];
		return $form;
	}

	public function signUpFormSucceeded(Form $form) {
		$values = $form->values;

		$user = new App\Model\User;
		$user->username = $values->username;
		$user->password = Passwords::hash($values->password);
		$user->email = $values->email;

		try {
			$this->users->persistAndFlush($user);
			$this->flashMessage('Registrace proběhla úspěšně.', 'success');
			$this->redirect('Homepage:');
		} catch (UniqueConstraintViolationException $e) {
			$form->addError('Toto uživatelské jméno nebo e-mail je již obsazeno.');
		} catch (PDOException $e) {
			$file = Debugger::log($e);
			$form->addError('Nastala neznámá chyba. Informace o chybě byly uloženy do souboru ' . basename($file));
		}
	}

	public function renderResetPassword($tid = null, $token = null) {
		$this->template->robots = 'noindex';
		if ($token && $tid) {
			$storedToken = $this->tokens->getById($tid);
			if ($storedToken && Passwords::verify($token, $storedToken->token)) {
				$this->template->token = $this->token = $token;
				$this->template->tid = $this->tid = $tid;
			} else {
				$this->flashMessage('Neplatný kód na změnu hesla.', 'danger');
			}
		}
	}

	protected function createComponentPasswordResetRequestForm() {
		$form = new Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$type = $form->addRadioList('type', null, ['username' => 'Přezdívka', 'email' => 'E-Mail'])->setDefaultValue('username');
		$type->getSeparatorPrototype()->setName(null);
		$type->setRequired('Vyber si e-mail nebo přezdívku.');

		$handle = $form->addText('handle');
		$handle->getControlPrototype()->autofocus = true;
		$handle->setRequired('Zadej prosím své uživatelské jméno nebo e-mail.');
		$handle->setOption('description', 'Na tvůj e-mail ti pošleme odkaz, pomocí kterého si můžeš heslo změnit.');

		$noSpam = $form->addText('nospam', 'Zadej „nospam“');
		$noSpam->addRule(Form::FILLED, 'Ošklivý spamovací robote!');
		$noSpam->addRule(Form::EQUAL, 'Ošklivý spamovací robote!', 'nospam');
		$noSpam->getLabelPrototype()->class('nospam');
		$noSpam->getControlPrototype()->class('nospam');

		$form->addSubmit('send', 'Obnovit heslo');

		$form->onSuccess[] = [$this, 'passwordResetRequestFormSucceeded'];
		return $form;
	}

	public function passwordResetRequestFormSucceeded(Form $form) {
		$type = $form->values->type === 'username' ? 'username' : 'email';
		$handle = $form->values->handle;

		$user = $this->users->getBy([$type => $handle]);

		if ($user) {
			$t = Strings::random();
			$token = new Token;
			$token->token = Passwords::hash($t);
			$token->ip = $this->context->getByType('Nette\Http\IRequest')->remoteAddress;
			$token->expiration = (new Nette\Utils\DateTime())->add(\DateInterval::createFromDateString('2 day'));
			$token->type = Token::PASSWORD;
			$user->tokens->add($token);
			$this->users->persistAndFlush($user);

			$mailTemplate = $this->createTemplate();

			$appDir = $this->context->parameters['appDir'];
			$mailTemplate->setFile($appDir . '/templates/Profile/resetPasswordMail.latte');

			$mailTemplate->url = $this->link('//Profile:resetPassword', ['tid' => $token->id, 'token' => $t]);
			$mailTemplate->username = $user->username;
			$mail = new Message;
			$mail->setFrom('admin@fan-club-penguin.cz')->addTo($user->email)->setHtmlBody($mailTemplate);

			$mailer = new SendmailMailer;
			$mailer->send($mail);

			$this->flashMessage('Na tvůj e-mail jsme ti poslali odkaz ke změně hesla.', 'success');
			$this->redirect('Homepage:');
		} else {
			if ($type === 'username') {
				$this->flashMessage('Uživatel s touto přezdívkou nebyl nalezen.', 'danger');
			} else {
				$this->flashMessage('Uživatel s tímto e-mailem nebyl nalezen.', 'danger');
			}
		}
	}

	protected function createComponentPasswordResetForm() {
		$form = new Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);

		$token = $form->addHidden('token', $this->token);
		$tid = $form->addHidden('tid', $this->tid);

		$password = $form->addPassword('password', 'Heslo');
		$password->getControlPrototype()->autofocus = true;
		$password->setRequired('Zadej prosím nové heslo.');

		$form->addSubmit('send', 'Změnit heslo');

		$form->onSuccess[] = [$this, 'passwordResetFormSucceeded'];
		return $form;
	}

	public function passwordResetFormSucceeded(Form $form) {
		$token = $form->values->token;
		$tid = $form->values->tid;
		$password = $form->values->password;

		if ($token && $tid) {
			$storedToken = $this->tokens->getById($tid);
			if ($storedToken && Passwords::verify($token, $storedToken->token)) {
				$user = $storedToken->user;
				$user->password = Passwords::hash($password);
				$this->users->persistAndFlush($user);

				$this->tokens->removeAndFlush($storedToken);
				$this->flashMessage('Heslo bylo změněno, můžeš se přihlásit.');
				$this->redirect('Sign:in');
			} else {
				$this->flashMessage('Neplatný kód na změnu hesla.', 'danger');
			}
		}
	}
}
