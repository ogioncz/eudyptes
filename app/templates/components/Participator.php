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
		$this->template->youParticipate = $meeting->visitors->get()->findById($this->presenter->user->identity->id)->count();

		$this->template->render();
	}

	public function handleParticipation($meetingId, $youParticipate) {
		if(!$this->presenter->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$meeting = $this->meetings->getById($meetingId);
		$userId = $this->presenter->user->identity->id;

		if($youParticipate) {
			$meeting->visitors->remove($userId);
		} else {
			$meeting->visitors->add($userId);
		}
		$this->meetings->persistAndFlush($meeting);

		$this->redirect('this');
	}
}
