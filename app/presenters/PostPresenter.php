<?php
namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;
use App;
use App\Model\Post;

class PostPresenter extends BasePresenter {
	/** @var App\Model\Formatter @inject */
	public $formatter;

	/** @var App\Model\PostRepository @inject */
	public $posts;

	/** @var App\Model\UserRepository @inject */
	public $users;

	public function renderShow($id) {
		$post = $this->posts->getById($id);
		if (!$post) {
			$this->error('Aktuálka nenalezena');
		}

		$this->template->post = $post;
	}

	public function renderList() {
		$posts = $this->posts->findAll()->orderBy(['timestamp' => 'DESC']);
		$this->template->posts = $posts;
	}

	protected function createComponentPostForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired();
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$form->addSubmit('send', 'Odeslat a zveřejnit');
		$form->onSuccess[] = $this->postFormSucceeded;

		return $form;
	}

	public function postFormSucceeded(Nette\Application\UI\Form $form) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->user->isAllowed('post', $this->action)) {
			$this->error('Pro vytváření či úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->values;

		if ($this->action === 'create') {
			$post = new Post;
		} else {
			$id = $this->getParameter('id');
			$post = $this->posts->getById($id);
			if (!$post) {
				$this->error('Aktuálka nenalezena.');
			}
		}
		$post->title = $values->title;
		$post->markdown = $values->markdown;

		$formatted = $this->formatter->format($post->markdown);
		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$post->content = $formatted['text'];

		if ($this->action === 'create') {
			$post->user = $this->users->getById($this->user->identity->id);
		}

		$this->posts->persistAndFlush($post);
		$this->flashMessage('Aktuálka byla odeslána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function actionCreate() {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->user->isAllowed('post', $this->action)) {
			$this->error('Pro vytváření příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}

	public function actionEdit($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->user->isAllowed('post', $this->action)) {
			$this->error('Pro úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$post = $this->posts->getById($id);
		if (!$post) {
			$this->error('Aktuálka nenalezena');
		}
		$data = $post->toArray();
		$this['postForm']->setDefaults($data);
	}

	public function renderRss() {
		$posts = $this->posts->findAll()->orderBy(['timestamp' => 'DESC'])->limitBy(15);
		$this->template->posts = $posts;
	}
}
