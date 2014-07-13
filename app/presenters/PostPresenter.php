<?php
namespace App\Presenters;

use Nette, Nextras\Forms\Rendering;


class PostPresenter extends BasePresenter {
	/** @var \Parsedown @inject */
	public $parsedown;
	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderShow($postId) {
		$post = $this->database->table('post')->get($postId);
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
		if(!$this->user->isInRole('admin')) {
			$this->error('You need to log in to create or edit posts');
		}
		$values = $form->getValues();
		$values['content'] = $this->parsedown->parse($values['markdown']);
		$postId = $this->getParameter('postId');
		
		if($postId) {
			$post = $this->database->table('post')->get($postId);
			$post->update($values);
		} else {
			$values['user_id'] = $this->user->identity->id;
			$post = $this->database->table('post')->insert($values);
		}

		$this->flashMessage('Aktuálka byla odeslána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function actionCreate() {
		if(!$this->user->isInRole('admin')) {
			$this->redirect('Sign:in');
		}
	}
	
	public function actionEdit($postId) {
		if(!$this->user->isInRole('admin')) {
			$this->redirect('Sign:in');
		}
		$post = $this->database->table('post')->get($postId);
		if(!$post) {
			$this->error('Aktuálka nenalezena');
		}
		$data = $post->toArray();
		$this['postForm']->setDefaults($data);
	}
}
