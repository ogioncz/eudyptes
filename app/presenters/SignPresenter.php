<?php

namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;

/**
 * SignPresenter handles user sign-ins and sign-outs.
 */
class SignPresenter extends BasePresenter {

	/** @persistent */
	public $backlink = '';

	protected function createComponentSignInForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('username', 'Uživatelské jméno:')->setRequired('Zadej prosím své uživatelské jméno.')->getControlPrototype()->autofocus = true;

		$form->addPassword('password', 'Heslo:')->setRequired('Zadej prosím své heslo.');

		$form->addCheckbox('remember', 'Zapamatovat přihlášení')->setDefaultValue(true);

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}

	public function signInFormSucceeded(Nette\Application\UI\Form $form) {
		$values = $form->values;

		if ($values->remember) {
			$this->user->setExpiration('14 days', false);
		} else {
			$this->user->setExpiration('20 minutes', true);
		}

		try {
			$this->user->login($values->username, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionOut() {
		$this->user->logout();
		$this->flashMessage('Byl jsi odhlášen.', 'info');
		$this->redirect('in');
	}

}
