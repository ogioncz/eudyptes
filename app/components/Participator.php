<?php

namespace App\Components;

use App;
use Nette;
use Nette\Application\UI\Control;
use Nextras\Forms\Rendering\Bs3FormRenderer;

class Participator extends Control {

	/** @var App\Model\Meeting */
	public $meeting;

	/** @var bool */
	public $youParticipate;

	/** @var callback */
	public $callback;

	public function __construct(App\Model\Meeting $meeting, $youParticipate, $callback) {
		$this->meeting = $meeting;
		$this->youParticipate = $youParticipate;
		$this->callback = $callback;
	}

	public function render() {
		$this->template->getLatte()->addFilter(null, [new App\Model\HelperLoader($this->presenter), 'loader']);
		$this->template->setFile(__DIR__ . '/participator.latte');

		$this->template->participants = $this->meeting->visitors;

		$this->template->render();
	}


	protected function createComponentParticipateForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->getElementPrototype()->class('ajax');
		$form->setRenderer(new Bs3FormRenderer);
		$form->form->getElementPrototype()->removeClass('form-horizontal');
		$form->form->getElementPrototype()->addClass('form-inline');
		$form->addHidden('action', $this->youParticipate ? 'unparticipate' : 'participate');
		$form->addHidden('id', $this->meeting->id);
		$form->addSubmit('send', $this->youParticipate ? 'Zrušit účast' : 'Zúčastnit se');
		$form->onSuccess[] = $this->callback;

		return $form;
	}
}
