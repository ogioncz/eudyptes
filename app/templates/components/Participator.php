<?php

namespace App\Components;

use Nette\Application\UI\Control;
use Nextras\Forms\Rendering;

class Participator extends Control {
	/** @persistent */
	public $youParticipate = false;

	/** @persistent */
	public $meetingId = null;

	public function render($meeting) {
		$this->template->getLatte()->addFilter(null, [new \App\Model\HelperLoader($this->presenter), 'loader']);
		$this->template->setFile(__DIR__ . '/participator.latte');

		$this->youParticipate = $this->template->youParticipate = $meeting->related('meeting_user')->where('user_id', $this->presenter->user->identity->id)->count();
		$this->template->participants = $meeting->related('meeting_user');
		$this->template->meeting = $meeting;
		$this->meetingId = $meeting->id;

		$this->template->render();
	}

	protected function createComponentParticipateForm() {
		$form = new \Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->form->getElementPrototype()->removeClass('form-horizontal');
		$form->form->getElementPrototype()->addClass('form-inline');
		$form->addHidden('action', $this->youParticipate ? 'unparticipate' : 'participate');
		$form->addHidden('id', $this->meetingId);
		$form->addSubmit('send', $this->youParticipate ? 'Zrušit účast' : 'Zúčastnit se');
		$form->onSuccess[] = $this->participateFormSucceeded;

		return $form;
	}

	public function participateFormSucceeded($form) {
		if(!$this->presenter->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		
		$values = $form->getValues();
		if($values->action == 'unparticipate') {
			$this->presenter->database->table('meeting_user')->where(['meeting_id' => $values->id, 'user_id' => $this->presenter->user->identity->id])->delete();
		} else {
			try {
				$this->presenter->database->table('meeting_user')->insert(['meeting_id' => $values->id, 'user_id' => $this->presenter->user->identity->id]);
			} catch(\PDOException $e) {
				if($e->getCode() != 23000) {
					throw $e;
				}
			}
		}
		
		$this->redirect('this');
	}
}
