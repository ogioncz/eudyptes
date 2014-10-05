<?php

namespace App\Presenters;

use App;
use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nextras\Forms\Rendering;

class ProfilePresenter extends BasePresenter {
	/** @var App\Model\UserRepository @inject */
	public $users;

	public function renderList() {
		$this->template->profiles = $this->users->findAll()->orderBy('username');
	}

	public function renderShow($id) {
		$profile = $this->users->getById($id);
		if (!$profile) {
			$this->error('Uživatel nenalezen');
		}
		if (file_exists($this->context->parameters['avatarStorage'] . '/' . $profile->id . 'm.png')) {
			$this->template->avatar = str_replace('♥basePath♥', $this->context->httpRequest->url->baseUrl, $this->context->parameters['avatarStoragePublic']) . '/' . $profile->id . 'm.png';
		}

		$this->template->isMe = $this->user->loggedIn && $this->user->identity->id === $profile->id;
		$this->template->ipAddress = $this->context->httpRequest->remoteAddress;
		$this->template->profile = $profile;
	}

	protected function createComponentSignUpForm() {
		$form = new Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$username = $form->addText('username', 'Přezdívka:');
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

		$form->onSuccess[] = $this->signUpFormSucceeded;
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
		} catch (\PDOException $e) {
			if (intVal($e->getCode()) === 23000) {
				$form->addError('Toto uživatelské jméno nebo e-mail je již obsazeno.');
			} else {
				$form->addError($e->getMessage());
			}
		}
	}
}
