<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Helpers\Formatting;
use App\Model\Meeting;
use Nette;
use Nette\Application\UI\Multiplier;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Json;
use Nextras\FormsRendering\Renderers;

/**
 * MeetingPresenter handles user-organised meetings and events.
 */
class MeetingPresenter extends BasePresenter {
	#[Nette\DI\Attributes\Inject]
	public Formatting\Formatter $formatter;

	#[Nette\DI\Attributes\Inject]
	public App\Model\MeetingRepository $meetings;

	#[Nette\DI\Attributes\Inject]
	public App\Model\UserRepository $users;

	#[Nette\DI\Attributes\Inject]
	public App\Model\HelperLoader $helperLoader;

	public function renderList(): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (isset($this->params['do']) && $this->params['do'] === 'participator-participation') {
			$this->getTemplate()->meetings = $this->meetings->findById($this['participator']->params['meetingId']);
		} else {
			$this->getTemplate()->meetings = $this->meetings->findUpcoming();
		}
	}

	protected function createComponentMeetingForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form();
		$form->addProtection();
		$renderer = new Renderers\Bs3FormRenderer();
		$form->setRenderer($renderer);

		$submit = $form->addSubmit('firstsend', 'Odeslat a zveřejnit');
		$submit->getControlPrototype()->addClass('hidden');
		$submit->onClick[] = [$this, 'meetingFormSucceeded'];

		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$dateDateControl = $form['date'] = new \Nextras\FormComponents\Controls\DateControl('Datum:');
		$dateDateControl->setRequired();
		$form->addText('server', 'Server:')->setRequired();

		$form->addDynamic('times', function(Container $time): void {
			$time->addTimePicker('time', 'čas')->setRequired();
			$time->addText('event', 'činnost')->setRequired();

			$time->addSubmit('remove', 'Odebrat')->setValidationScope([])->onClick[] = function(SubmitButton $button): void {
				$replicator = $button->getParent()->getParent();
				$replicator->remove($button->getParent(), true);
			};
		}, 1, true);

		$form->addSubmit('add', 'Přidat')->setValidationScope([])->onClick[] = function(SubmitButton $button): void {
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

	public function meetingFormSucceeded(SubmitButton $submit): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $submit->getForm()->getValues();
		$formatted = $this->formatter->format($values->markdown);

		if (is_countable($formatted['errors']) ? \count($formatted['errors']) : 0) {
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

		[$hour, $minute] = explode(':', $program[0]['time']);
		$meeting->program = Json::encode($program);
		$meeting->date = $values->date->setTime((int) $hour, (int) $minute);

		if ($this->getAction() === 'create') {
			$meeting->ip = $this->getHttpRequest()->getRemoteAddress();
			$meeting->user = $this->users->getById($this->getUser()->getIdentity()->getId());
		}
		$this->meetings->persistAndFlush($meeting);

		$this->flashMessage('Sraz byl odeslán.', 'success');
		$this->redirect('list');
	}

	public function meetingFormPreview(SubmitButton $button): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		$formatted = $this->formatter->format($values['markdown']);

		if (is_countable($formatted['errors']) ? \count($formatted['errors']) : 0) {
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

		[$hour, $minute] = explode(':', $program[0]['time']);
		$meeting->program = Json::encode($program);
		$meeting->date = $values->date->setTime((int) $hour, (int) $minute);
		$meeting->user = $this->users->getById($this->getUser()->getIdentity()->getId());

		$this->getTemplate()->preview = $meeting;

		$this->flashMessage('Toto je jen náhled, sraz zatím nebyl uložen.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate(): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('meeting', $this->getAction())) {
			$this->error('Pro založení srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}

	public function actionEdit($id): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$meeting = $this->meetings->getById($id);
		if ($meeting === null) {
			$this->error('Sraz nenalezen.');
		}
		if (!$this->allowed($meeting, 'edit')) {
			$this->error('Pro úpravu cizího srazu musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$data = $meeting->toArray();
		$data['times'] = Json::decode($meeting->program, Json::FORCE_ARRAY);
		$this['meetingForm']->setDefaults($data);
	}

	protected function createComponentDeleteForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form();
		$form->addProtection();
		$form->setRenderer(new Renderers\Bs3FormRenderer());

		$submit = $form->addSubmit('send', 'Ano, smazat');
		$submit->getControlPrototype()->removeClass('btn-primary')->addClass('btn-danger');
		$form->onSuccess[] = [$this, 'deleteFormSucceeded'];

		return $form;
	}

	public function deleteFormSucceeded(): void {
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

	public function actionDelete($id): void {
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
	 */
	protected function createComponentParticipator(): Multiplier {
		return new Multiplier(function($meetingId) {
			$userId = $this->getUser()->getIdentity()->getId();
			$meeting = $this->meetings->getById($meetingId);
			$youParticipate = array_reduce(iterator_to_array($meeting->visitors->get()), function($carry, $visitor) use ($userId) {
				if ($visitor->id === $userId) {
					return true;
				}

				return $carry;
			}, false);

			return new App\Components\Participator($meeting, $youParticipate, [$this, 'participatorClicked'], $this->helperLoader);
		});
	}

	public function participatorClicked(Nette\Application\UI\Form $form, $values): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$meeting = $this->meetings->getById($values->id);
		$userId = $this->getUser()->getIdentity()->getId();
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

		$form->getComponent('action')->value = $youParticipate ? 'unparticipate' : 'participate';
		$form->getComponent('send')->caption = $youParticipate ? 'Zrušit účast' : 'Zúčastnit se';
		if (!$this->isAjax()) {
			$this->redirect('this');
		} else {
			$this->redrawControl('meetings');
		}
	}
}
