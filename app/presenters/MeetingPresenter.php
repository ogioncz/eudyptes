<?php

namespace App\Presenters;

use Nette;
use App;
use App\Helpers\Formatting;
use App\Model\Meeting;
use Nextras\Forms\Rendering;
use Nette\Utils\Json;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Application\UI\Multiplier;

/**
 * MeetingPresenter handles user-organised meetings and events.
 */
class MeetingPresenter extends BasePresenter {
	/** @var Formatting\Formatter @inject */
	public $formatter;

	/** @var App\Model\MeetingRepository @inject */
	public $meetings;

	/** @var App\Model\UserRepository @inject */
	public $users;

	public function renderList() {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (isset($this->params['do']) && $this->params['do'] === 'participator-participation') {
			$this->getTemplate()->meetings = $this->meetings->findById($this['participator']->params['meetingId']);
		} else {
			$this->getTemplate()->meetings = $this->meetings->findUpcoming();
		}
	}

	protected function createComponentMeetingForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$renderer = new Rendering\Bs3FormRenderer;
		$form->setRenderer($renderer);

		$submit = $form->addSubmit('firstsend', 'Odeslat a zveřejnit');
		$submit->getControlPrototype()->addClass('hidden');
		$submit->onClick[] = [$this, 'meetingFormSucceeded'];

		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$form->addDatePicker('date', 'Datum:')->setRequired();
		$form->addText('server', 'Server:')->setRequired();

		$form->addDynamic('times', function (Container $time) {
			$time->addTimePicker('time', 'čas')->setRequired();
			$time->addText('event', 'činnost')->setRequired();

			$time->addSubmit('remove', 'Odebrat')->setValidationScope(false)->onClick[] = function(SubmitButton $button) {
				$replicator = $button->getParent()->getParent();
				$replicator->remove($button->getParent(), true);
			};
		}, 1, true);

		$form->addSubmit('add', 'Přidat')->setValidationScope(false)->onClick[] = function(SubmitButton $button) {
			$button->getParent()['times']->createOne();
		};

		$form->addTextArea('markdown', 'Popis:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = [$this, 'meetingFormPreview'];
		$previewButton->getControlPrototype()->addClass('ajax');

		$submit = $form->addSubmit('send', 'Odeslat a zveřejnit');
		$submit->onClick[] = [$this, 'meetingFormSucceeded'];
		$renderer->primaryButton = $submit;

		return $form;
	}

	public function meetingFormSucceeded(SubmitButton $submit) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $submit->getForm()->getValues();
		$formatted = $this->formatter->format($values->markdown);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		if ($this->getAction() === 'create') {
			$meeting = new Meeting();
		} else {
			$id = $this->getParameter('id');
			$meeting = $this->meetings->getById($id);
			if (!$meeting) {
				$this->error('Sraz nenalezen.');
			}
			if (!$this->allowed($meeting, $this->getAction())) {
				$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
			}
		}

		$meeting->title = $values->title;
		$meeting->server = $values->server;
		$meeting->markdown = $values->markdown;
		$meeting->description = $formatted['text'];

		$program = [];
		foreach ($values->times as $time) {
			$program[] = ['time' => $time['time']->format('H:i'), 'event' => $time['event']];
		}

		list($hour, $minute) = explode(':', $program[0]['time']);
		$meeting->program = Json::encode($program);
		$meeting->date = $values->date->setTime($hour, $minute);

		if ($this->getAction() === 'create') {
			$meeting->ip = $this->getContext()->getByType('Nette\Http\IRequest')->remoteAddress;
			$meeting->user = $this->users->getById($this->getUser()->getIdentity()->id);
		}
		$this->meetings->persistAndFlush($meeting);

		$this->flashMessage('Sraz byl odeslán.', 'success');
		$this->redirect('list');
	}

	public function meetingFormPreview(SubmitButton $button) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$meeting = new Meeting();

		$meeting->title = $values->title;
		$meeting->server = $values->server;
		$meeting->markdown = $values->markdown;
		$meeting->description = $formatted['text'];

		$program = [];
		foreach ($values->times as $time) {
			$program[] = ['time' => $time['time']->format('H:i'), 'event' => $time['event']];
		}

		list($hour, $minute) = explode(':', $program[0]['time']);
		$meeting->program = Json::encode($program);
		$meeting->date = $values->date->setTime($hour, $minute);
		$meeting->user = $this->users->getById($this->getUser()->getIdentity()->id);

		$this->getTemplate()->preview = $meeting;

		$this->flashMessage('Toto je jen náhled, sraz zatím nebyl uložen.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate() {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('meeting', $this->getAction())) {
			$this->error('Pro založení srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}

	public function actionEdit($id) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		/** @var Meeting $meeting */
		$meeting = $this->meetings->getById($id);
		if (!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if (!$this->allowed($meeting, 'edit')) {
			$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$data = $meeting->toArray();
		$data['times'] = Json::decode($meeting->program, Json::FORCE_ARRAY);
		$this['meetingForm']->setDefaults($data);
	}

	protected function createComponentDeleteForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);

		$submit = $form->addSubmit('send', 'Ano, smazat');
		$submit->getControlPrototype()->removeClass('btn-primary')->addClass('btn-danger');
		$form->onSuccess[] = [$this, 'deleteFormSucceeded'];

		return $form;
	}

	public function deleteFormSucceeded() {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$meeting = $this->meetings->getById($this->getParameter('id'));
		if (!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if (!$this->allowed($meeting, $this->getAction())) {
			$this->error('Pro smazání cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$this->meetings->removeAndFlush($meeting);

		$this->flashMessage('Sraz byl odstraněn.', 'success');
		$this->redirect('list');
	}

	public function actionDelete($id) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$meeting = $this->meetings->getById($id);
		if (!$meeting) {
			$this->error('Sraz nenalezen.');
		}
		if (!$this->allowed('meeting', $this->getAction())) {
			$this->error('Pro smazání cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$this->getTemplate()->meeting = $meeting;
	}

	/**
	* Participator control factory.
	* @return Multiplier
	*/
	protected function createComponentParticipator() {
		return new Multiplier(function($meetingId) {
			$userId = $this->getUser()->getIdentity()->id;
			$meeting = $this->meetings->getById($meetingId);
			$youParticipate = array_reduce(iterator_to_array($meeting->visitors->get()), function($carry, $visitor) use ($userId) {
				if($visitor->id === $userId) {
					return true;
				}
				return $carry;
			}, false);

			return new App\Components\Participator($meeting, $youParticipate, [$this, 'participatorClicked']);
		});
	}

	public function participatorClicked(Nette\Application\UI\Form $form, $values) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$meeting = $this->meetings->getById($values->id);
		$userId = $this->getUser()->getIdentity()->id;
		$youParticipate = $values->action === 'unparticipate';
		try {
			if ($youParticipate) {
				$meeting->visitors->remove($userId);
			} else {
				$meeting->visitors->add($userId);
			}
			$youParticipate = !$youParticipate;
			$this->meetings->persistAndFlush($meeting);
		} catch (\Exception $e) {
			\Tracy\Debugger::log($e);
		}

		$form->getComponents('action')->value = $youParticipate ? 'unparticipate' : 'participate';
		$form->getComponents('send')->caption = $youParticipate ? 'Zrušit účast' : 'Zúčastnit se';
		if (!$this->isAjax()) {
			$this->redirect('this');
		} else {
			$this->redrawControl('meetings');
		}
	}
}
