<?php
namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;

class PostPresenter extends BasePresenter {
	/** @var \App\Model\Formatter @inject */
	public $formatter;

	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderShow($id) {
		$post = $this->database->table('post')->get($id);
		if(!$post) {
			$this->error('Aktuálka nenalezena');
		}

		$this->template->post = $post;
	}

	protected function createComponentPostForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired();
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15);

		$form->addSubmit('send', 'Odeslat a zveřejnit');
		$form->onSuccess[] = $this->postFormSucceeded;

		return $form;
	}
	
	public function postFormSucceeded($form) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro vytváření či úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();
		$formatted = $this->formatter->format($values['markdown']);

		if(count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$values['content'] = $formatted['text'];
		$id = $this->getParameter('id');
		
		if($id) {
			$post = $this->database->table('post')->get($id);
			$post->update($values);
		} else {
			$values['user_id'] = $this->user->identity->id;
			$post = $this->database->table('post')->insert($values);
		}

		$this->flashMessage('Aktuálka byla odeslána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function actionCreate() {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro vytváření příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}
	
	public function actionEdit($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$post = $this->database->table('post')->get($id);
		if(!$post) {
			$this->error('Aktuálka nenalezena');
		}
		$data = $post->toArray();
		$this['postForm']->setDefaults($data);
	}
}
