<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nextras\FormsRendering\Renderers;

/**
 * SignPresenter handles user sign-ins and sign-outs.
 */
class SignPresenter extends BasePresenter {

	#[Nette\Application\Attributes\Persistent]
	public $backlink = '';

	protected function createComponentSignInForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Renderers\Bs3FormRenderer);
		$form->addText('username', 'Uživatelské jméno:')->setRequired('Zadej prosím své uživatelské jméno.')->getControlPrototype()->autofocus = true;

		$form->addPassword('password', 'Heslo:')->setRequired('Zadej prosím své heslo.');

		$form->addCheckbox('remember', 'Zapamatovat přihlášení')->setDefaultValue(true);

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}

	public function signInFormSucceeded(Nette\Application\UI\Form $form): void {
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

	public function actionOut(): void {
		$this->getUser()->logout();
		$this->flashMessage('Byl jsi odhlášen.', 'info');
		$this->redirect('in');
	}

}
