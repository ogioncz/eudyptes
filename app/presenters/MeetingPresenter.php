<?php

namespace App\Presenters;

use Nette;
use App;
use Nextras\Forms\Rendering;
use Nextras\Forms\Controls;
use Nette\Utils\Json;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;

/**
 * Meeting presenter.
 */
class MeetingPresenter extends BasePresenter {
	/** @var \App\Model\Formatter @inject */
	public $formatter;

	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderList($sent = false) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$this->template->meetings = $this->database->table('meeting')->where('date >= CURDATE()')->order('date, start');	
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
				$form->remove($button->parent, TRUE);
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

		$values = $submit->form->getValues();
		$data['title'] = $values['title'];
		$data['server'] = $values['server'];
		$data['date'] = $values['date'];
		$data['markdown'] = $values['markdown'];
		$data['description'] = $this->formatter->format($values['markdown']);
		
		$program = [];
		foreach($values['times'] as $time) {
			$program[] = ['time' => $time['time']->format('H:i'), 'event' => $time['event']];
		}
		$data['start'] = $program[0]['time'];
		$data['program'] = Json::encode($program);

		if($this->getAction() === 'create') {
			$data['ip'] = $this->context->httpRequest->remoteAddress;
			$data['user_id'] = $this->user->identity->id;
			$meeting = $this->database->table('meeting')->insert($data);
		} else {
			$id = $this->getParameter('id');
			$meeting = $this->database->table('meeting')->get($id);
			if(!$meeting) {
				$this->error('Sraz nenalezen.');
			}

			if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
				$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
			}

			$meeting->update($data);
		}

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
		$meeting = $this->database->table('meeting')->where('id', $id)->fetch();
		if(!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if(!$this->user->isInRole('admin') && $meeting->user->id !== $this->user->identity->id) {
			$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$data = $meeting->toArray();
		$data['times'] = Json::decode($data['program'], true);
		$this['meetingForm']->setDefaults($data);
	}

	/**
	* Participator control factory.
	* @return App\Components\Participator
	*/
	protected function createComponentParticipator() {
		$participator = new App\Components\Participator;
		return $participator;
	}
}
