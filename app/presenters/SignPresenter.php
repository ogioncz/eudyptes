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
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', false);
		} else {
			$this->getUser()->setExpiration('20 minutes', true);
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessage('Byl jsi odhlášen.', 'info');
		$this->redirect('in');
	}

}
