<?php

namespace App\Presenters;

use Nette, App\Model, Nextras\Forms\Rendering;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter {

	/** @persistent */
	public $backlink = '';

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('username', 'Uživatelské jméno:')->setRequired('Zadej prosím své uživatelské jméno.');

		$form->addPassword('password', 'Heslo:')->setRequired('Zadej prosím své heslo.');

		$form->addCheckbox('remember', 'Zapamatovat přihlášení');

		$form->addSubmit('send', 'Přihlásit se');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}


	public function signInFormSucceeded($form) {
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');

		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byl jsi odhlášen.');
		$this->redirect('in');
	}

}
