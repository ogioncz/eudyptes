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

	/** @var callable */
	public $callback;

	public function __construct(App\Model\Meeting $meeting, $youParticipate, callable $callback) {
		parent::__construct();
		$this->meeting = $meeting;
		$this->youParticipate = $youParticipate;
		$this->callback = $callback;
	}

	public function render() {
		$template = $this->getTemplate();

		$template->getLatte()->addFilter(null, [$this->getPresenter()->getContext()->getByType('App\Model\HelperLoader'), 'loader']);
		$template->setFile(__DIR__ . '/participator.latte');

		$template->participants = $this->meeting->visitors;

		$template->render();
	}


	protected function createComponentParticipateForm() {
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
