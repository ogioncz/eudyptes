<?php

declare(strict_types=1);

namespace App\Components;

use App;
use Nette;
use Nette\Application\UI\Control;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;

class Participator extends Control {
	/** @var callable */
	private $callback;

	public function __construct(
		private App\Model\Meeting $meeting,
		private bool $youParticipate,
		callable $callback,
		private \App\Model\HelperLoader $helperLoader,
	) {
		$this->callback = $callback;
	}

	public function render(): void {
		$template = $this->getTemplate();

		$template->getLatte()->addFilterLoader([$this->helperLoader, 'loader']);
		$template->setFile(__DIR__ . '/participator.latte');

		$template->participants = $this->meeting->visitors;

		$template->render();
	}


	protected function createComponentParticipateForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->getElementPrototype()->class('ajax');
		$form->setRenderer(new Bs3FormRenderer);
		$form->getElementPrototype()->removeClass('form-horizontal');
		$form->getElementPrototype()->addClass('form-inline');
		$form->addHidden('action', $this->youParticipate ? 'unparticipate' : 'participate');
		$form->addHidden('id', $this->meeting->id);
		$form->addSubmit('send', $this->youParticipate ? 'Zrušit účast' : 'Zúčastnit se');
		$form->onSuccess[] = $this->callback;

		return $form;
	}
}
