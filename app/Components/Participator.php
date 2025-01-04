<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\HelperLoader;
use App\Model\Orm\Meeting\Meeting;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;

class Participator extends Control {
	/** @var callable */
	private $callback;

	public function __construct(
		private readonly Meeting $meeting,
		private readonly bool $youParticipate,
		callable $callback,
		private readonly HelperLoader $helperLoader,
	) {
		$this->callback = $callback;
	}

	public function render(): void {
		$template = $this->getTemplate();

		$template->getLatte()->addFilterLoader($this->helperLoader->loader(...));
		$template->setFile(__DIR__ . '/participator.latte');

		$template->participants = $this->meeting->visitors;

		$template->render();
	}

	protected function createComponentParticipateForm(): Form {
		$form = new Form();
		$form->addProtection();
		$form->getElementPrototype()->class('ajax');
		$form->setRenderer(new Bs3FormRenderer());
		$form->getElementPrototype()->removeClass('form-horizontal');
		$form->getElementPrototype()->addClass('form-inline');
		$form->addHidden('action', $this->youParticipate ? 'unparticipate' : 'participate');
		$form->addHidden('id', $this->meeting->id);
		$form->addSubmit('send', $this->youParticipate ? 'Zrušit účast' : 'Zúčastnit se');
		$form->onSuccess[] = $this->callback;

		return $form;
	}
}
