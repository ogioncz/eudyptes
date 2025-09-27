<?php

declare(strict_types=1);

namespace App\Presentation\Profile;

use App\Model\Orm\Stamp\StampRepository;
use App\Model\Orm\Token\Token;
use App\Model\Orm\Token\TokenRepository;
use App\Model\Orm\User\User;
use App\Model\Orm\User\UserRepository;
use App\Presentation\BasePresenter;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Security\Passwords;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nextras\Dbal\Drivers\Exception\UniqueConstraintViolationException;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use PDOException;
use Tracy\Debugger;

/**
 * ProfilePresenter displays user profiles.
 */
class ProfilePresenter extends BasePresenter {
	#[Inject]
	public Container $context;

	#[Inject]
	public UserRepository $users;

	#[Inject]
	public TokenRepository $tokens;

	#[Inject]
	public StampRepository $stamps;

	#[Persistent]
	public $token;

	#[Persistent]
	public $tid;

	#[Persistent]
	public ?User $profile = null;

	#[Inject]
	public Passwords $passwords;

	public function renderList(): void {
		$template = $this->getTemplate();
		$template->profiles = $this->users->findAll()->orderBy('username');
	}

	public function renderShow($id): void {
		$this->profile = $this->users->getById($id);
		if (!$this->profile) {
			$this->error('Uživatel nenalezen');
		}
		$template = $this->getTemplate();
		if (file_exists($this->context->parameters['avatarStorage'] . '/' . $this->profile->id . 'm.png')) {
			$template->avatar = str_replace('♥basePath♥', $this->getHttpRequest()->getUrl()->getBaseUrl(), $this->context->parameters['avatarStoragePublic']) . '/' . $this->profile->id . 'm.png';
		}

		$template->ipAddress = $this->getHttpRequest()->getRemoteAddress();
		$template->profile = $this->profile;
		$template->stamps = $this->stamps->findAll();
	}

	public function actionEdit($id): void {
		if (!$this->getUser()->isLoggedIn()) {
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

	protected function createComponentProfileForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->setRenderer(new Bs3FormRenderer());
		$username = $form->addText('username', 'Přezdívka:');

		if (!$this->allowed($this->profile, 'rename')) {
			$username->setDisabled(true);
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

		$form->onSuccess[] = $this->profileFormSucceeded(...);

		return $form;
	}

	public function profileFormSucceeded(Form $form): void {
		$values = $form->getValues();
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
				Image::fromBlank(100, 100, Image::rgb(0, 0, 0, 127))->place($resized, '50%', '50%')->save($medium);
			} catch (Exception $e) {
				$form->addError('Chyba při zpracování avataru.');
				Debugger::log($e);
			}
		}

		if ($values->password) {
			$user->password = $this->passwords->hash($values->password);
		}

		try {
			$this->users->persistAndFlush($user);
			$this->flashMessage('Profil byl úspěšně upraven.', 'success');
			$this->redirect('show', $user->id);
		} catch (UniqueConstraintViolationException) {
			$form->addError($this->allowed($user, 'rename') ? 'Tento e-mail nebo přezdívka jsou již obsazeny.' : 'Tento e-mail je již obsazen.');
		} catch (PDOException $e) {
			$file = Debugger::log($e);
			$form->addError('Nastala neznámá chyba. Informace o chybě byly uloženy do souboru ' . basename((string) $file));
		}
	}

	protected function createComponentSignUpForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->setRenderer(new Bs3FormRenderer());
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

		$form->onSuccess[] = $this->signUpFormSucceeded(...);

		return $form;
	}

	public function signUpFormSucceeded(Form $form): void {
		$values = $form->getValues();

		$user = new User();
		$user->username = $values->username;
		$user->password = $this->passwords->hash($values->password);
		$user->email = $values->email;

		try {
			$this->users->persistAndFlush($user);
			$this->flashMessage('Registrace proběhla úspěšně.', 'success');
			$this->redirect('Homepage:');
		} catch (UniqueConstraintViolationException) {
			$form->addError('Toto uživatelské jméno nebo e-mail je již obsazeno.');
		} catch (PDOException $e) {
			$file = Debugger::log($e);
			$form->addError('Nastala neznámá chyba. Informace o chybě byly uloženy do souboru ' . basename((string) $file));
		}
	}

	public function renderResetPassword($tid = null, $token = null): void {
		$template = $this->getTemplate();
		if ($token && $tid) {
			$storedToken = $this->tokens->getById($tid);
			if ($storedToken && $this->passwords->verify($token, $storedToken->token)) {
				$template->token = $this->token = $token;
				$template->tid = $this->tid = $tid;
			} else {
				$this->flashMessage('Neplatný kód na změnu hesla.', 'danger');
			}
		}
	}

	protected function createComponentPasswordResetRequestForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->setRenderer(new Bs3FormRenderer());
		$type = $form->addRadioList('type', null, ['username' => 'Přezdívka', 'email' => 'E-Mail'])->setDefaultValue('username');
		$type->getSeparatorPrototype()->setName('');
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

		$form->onSuccess[] = $this->passwordResetRequestFormSucceeded(...);

		return $form;
	}

	public function passwordResetRequestFormSucceeded(Form $form): void {
		$type = $form->getValues()->type === 'username' ? 'username' : 'email';
		$handle = $form->getValues()->handle;

		$user = $this->users->getBy([$type => $handle]);

		if ($user) {
			$t = Random::generate();
			$token = new Token();
			$token->token = $this->passwords->hash($t);
			$token->ip = $this->getHttpRequest()->getRemoteAddress();
			$token->expiration = (new DateTimeImmutable())->add(DateInterval::createFromDateString('2 day'));
			$token->type = Token::PASSWORD;
			$user->tokens->add($token);
			$this->users->persistAndFlush($user);

			$mailTemplate = $this->createTemplate();

			$mailTemplate->setFile(__DIR__ . '/resetPasswordMail.latte');

			$mailTemplate->url = $this->link('//Profile:resetPassword', ['tid' => $token->id, 'token' => $t]);
			$mailTemplate->username = $user->username;
			$mail = new Message();
			$mail->setFrom('admin@fan-club-penguin.cz')->addTo($user->email)->setHtmlBody((string) $mailTemplate);

			$mailer = new SendmailMailer();
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

	protected function createComponentPasswordResetForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->setRenderer(new Bs3FormRenderer());

		$token = $form->addHidden('token', $this->token);
		$tid = $form->addHidden('tid', $this->tid);

		$password = $form->addPassword('password', 'Heslo');
		$password->getControlPrototype()->autofocus = true;
		$password->setRequired('Zadej prosím nové heslo.');

		$form->addSubmit('send', 'Změnit heslo');

		$form->onSuccess[] = $this->passwordResetFormSucceeded(...);

		return $form;
	}

	public function passwordResetFormSucceeded(Form $form): void {
		$token = $form->getValues()->token;
		$tid = $form->getValues()->tid;
		$password = $form->getValues()->password;

		if ($token && $tid) {
			$storedToken = $this->tokens->getById($tid);
			if ($storedToken && $this->passwords->verify($token, $storedToken->token)) {
				$user = $storedToken->user;
				$user->password = $this->passwords->hash($password);
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
