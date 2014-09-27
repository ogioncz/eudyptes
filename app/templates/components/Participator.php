<?php

namespace App\Components;

use App;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;
use Nextras\Forms\Rendering;

class Participator extends Control {

	/** @var App\Model\MeetingRepository */
	public $meetings;

	/** @var App\Model\UserRepository */
	public $users;

	public function __construct(App\Model\MeetingRepository $meetings, App\Model\UserRepository $users) {
		$this->meetings = $meetings;
		$this->users = $users;
	}

	public function render($meeting) {
		$this->template->getLatte()->addFilter(null, [new \App\Model\HelperLoader($this->presenter), 'loader']);
		$this->template->setFile(__DIR__ . '/participator.latte');

		$this->template->meeting = $meeting;
		$this->template->participants = $meeting->visitors;

		$this->template->render();
	}

	protected function createComponentParticipateForm() {
		$cb = $this->participateFormSucceeded;
		$meetings = $this->meetings;
		$userId = $this->presenter->user->identity->id;

		return new Multiplier(function($meetingId) use ($cb, $meetings, $userId) {
			$youParticipate = array_reduce(iterator_to_array($meetings->getById($meetingId)->visitors->get()), function($carry, $visitor) use ($userId) {
				if($visitor->id === $userId) {
					return true;
				}
				return $carry;
			}, false);

			$form = new \Nette\Application\UI\Form;
			$form->setRenderer(new Rendering\Bs3FormRenderer);
			$form->form->getElementPrototype()->removeClass('form-horizontal');
			$form->form->getElementPrototype()->addClass('form-inline');
			$form->addHidden('action', $youParticipate ? 'unparticipate' : 'participate');
			$form->addHidden('id', $meetingId);
			$form->addSubmit('send', $youParticipate ? 'Zrušit účast' : 'Zúčastnit se');
			$form->onSuccess[] = $cb;

			return $form;
		});
	}

	public function participateFormSucceeded($form) {
		if(!$this->presenter->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		
		$values = $form->values;
		$userId = $this->presenter->user->identity->id;
		$meeting = $this->meetings->getById($values->id);

		if($values->action === 'unparticipate') {
			$meeting->visitors->remove($userId);
		} else {
			$meeting->visitors->add($userId);
		}
		$this->meetings->persistAndFlush($meeting);
		
		$this->redirect('this');
	}
}
