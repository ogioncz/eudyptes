<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;

/**
 * SignPresenter handles user sign-ins and sign-outs.
 */
class SignPresenter extends BasePresenter {
	#[Persistent]
	public $backlink = '';

	protected function createComponentSignInForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->setRenderer(new Bs3FormRenderer());
		$form->addText('username', 'Uživatelské jméno:')->setRequired('Zadej prosím své uživatelské jméno.')->getControlPrototype()->autofocus = true;

		$form->addPassword('password', 'Heslo:')->setRequired('Zadej prosím své heslo.');

		$form->addCheckbox('remember', 'Zapamatovat přihlášení')->setDefaultValue(true);

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];

		return $form;
	}

	public function signInFormSucceeded(Form $form): void {
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
		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionOut(): void {
		$this->getUser()->logout();
		$this->flashMessage('Byl jsi odhlášen.', 'info');
		$this->redirect('in');
	}
}
