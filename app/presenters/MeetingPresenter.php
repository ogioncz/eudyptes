<?php

namespace App\Presenters;

use Nette;
use App;
use App\Model\Meeting;
use Nextras\Forms\Rendering;
use Nette\Utils\Json;
use Nette\Utils\DateTime;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;

class MeetingPresenter extends BasePresenter {
	/** @var App\Model\Formatter @inject */
	public $formatter;

	/** @var App\Model\MeetingRepository @inject */
	public $meetings;

	/** @var App\Model\UserRepository @inject */
	public $users;

	public function renderList() {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$this->template->meetings = $this->meetings->findUpcoming();
	}

	protected function createComponentMeetingForm() {
		$form = new Nette\Application\UI\Form;
		$renderer = new Rendering\Bs3FormRenderer;
		$form->setRenderer($renderer);

		$submit = $form->addSubmit('firstsend', 'Odeslat a zveřejnit');
		$submit->getControlPrototype()->addClass('hidden');
		$submit->onClick[] = $this->meetingFormSucceeded;

		$form->addText('title', 'Nadpis:')->setRequired();
		$form->addDatePicker('date', 'Datum:')->setRequired();
		$form->addText('server', 'Server:')->setRequired();

		$form->addDynamic('times', function (Container $time) {
			$time->addTimePicker('time', 'čas')->setRequired();
			$time->addText('event', 'činnost')->setRequired();

			$time->addSubmit('remove', 'Odebrat')->setValidationScope(false)->onClick[] = function(SubmitButton $button) {
				$form = $button->parent->parent;
				$form->remove($button->parent, true);
			};
		}, 1, true);

		$form->addSubmit('add', 'Přidat')->setValidationScope(false)->onClick[] = function(SubmitButton $button) {
			$button->parent['times']->createOne();
		};
		
		$form->addTextArea('markdown', 'Popis:')->setRequired()->getControlPrototype()->addRows(15);

		$submit = $form->addSubmit('send', 'Odeslat a zveřejnit');
		$submit->onClick[] = $this->meetingFormSucceeded;
		$renderer->primaryButton = $submit;

		return $form;
	}
	
	public function meetingFormSucceeded(SubmitButton $submit) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $submit->form->values;
		$formatted = $this->formatter->format($values->markdown);

		if(count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		/** @var Meeting $meeting */
		$meeting = null;
		if($this->action === 'create') {
			$meeting = new Meeting();
		} else {
			$id = $this->getParameter('id');
			$meeting = $this->meetings->getById($id);
			if(!$meeting) {
				$this->error('Sraz nenalezen.');
			}
			if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
				$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
			}
		}

		$meeting->title = $values->title;
		$meeting->server = $values->server;
		$meeting->date = $values->date;
		$meeting->markdown = $values->markdown;
		$meeting->description = $formatted['text'];
		
		$program = [];
		foreach($values->times as $time) {
			$program[] = ['time' => $time['time']->format('H:i'), 'event' => $time['event']];
		}

		list($hour, $minute) = explode(':', $program[0]['time']);
		$meeting->start = $values->date->setTime($hour, $minute);
		$meeting->program = Json::encode($program);

		if($this->action === 'create') {
			$meeting->ip = $this->context->httpRequest->remoteAddress;
			$meeting->user = $this->users->getById($this->user->identity->id);
		}
		$this->meetings->persistAndFlush($meeting);

		$this->flashMessage('Sraz byl odeslán.', 'success');
		$this->redirect('list');
	}

	public function actionCreate() {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
	}
	
	public function actionEdit($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		/** @var Meeting $meeting */
		$meeting = $this->meetings->getById($id);
		if(!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
			$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$data = $meeting->toArray();
		$data['times'] = Json::decode($meeting->program, Json::FORCE_ARRAY);
		$this['meetingForm']->setDefaults($data);
	}

	protected function createComponentDeleteForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);

		$submit = $form->addSubmit('send', 'Ano, smazat');
		$submit->getControlPrototype()->removeClass('btn-primary')->addClass('btn-danger');
		$form->onSuccess[] = $this->deleteFormSucceeded;

		return $form;
	}

	public function deleteFormSucceeded() {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$meeting = $this->meetings->getById($this->getParameter('id'));
		if(!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
			$this->error('Pro smazání cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$this->meetings->removeAndFlush($meeting);

		$this->flashMessage('Sraz byl odstraněn.', 'success');
		$this->redirect('list');
	}

	public function actionDelete($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$meeting = $this->meetings->getById($id);
		if(!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
			$this->error('Pro smazání cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$this->template->meeting = $meeting;
	}

	/**
	* Participator control factory.
	* @return App\Components\Participator
	*/
	protected function createComponentParticipator() {
		$participator = new App\Components\Participator($this->meetings, $this->users);
		return $participator;
	}
}
