<?php
namespace App\Presenters;

use Nette;
use Nette\Caching\Cache;
use Nette\Forms\Controls\SubmitButton;
use Nextras\Forms\Rendering;
use App;
use App\Model\Post;
use App\Model\PostRevision;

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
		$posts = $this->posts->findAll()->orderBy(['createdAt' => 'DESC']);
		$this->template->posts = $posts;
	}

	protected function createComponentPostForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = $this->postFormPreview;
		$previewButton->getControlPrototype()->addClass('ajax');

		$submitButton = $form->addSubmit('send', 'Odeslat a zveřejnit');
		$submitButton->onClick[] = $this->postFormSucceeded;
		$form->renderer->primaryButton = $submitButton;

		return $form;
	}

	public function postFormSucceeded(SubmitButton $button) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->form->values;

		if ($this->action === 'create') {
			$post = new Post;
		} else {
			$id = $this->getParameter('id');
			$post = $this->posts->getById($id);
			if (!$post) {
				$this->error('Aktuálka nenalezena.');
			}
		}

		if (!$this->allowed($this->action === 'create' ? 'post' : $post, $this->action)) {
			$this->error('Pro vytváření či úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$formatted = $this->formatter->format($values->markdown);
		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		if ($this->action === 'create') {
			$post->user = $this->users->getById($this->user->identity->id);
		}

		$this->posts->persistAndFlush($post);

		$revision = new PostRevision;
		$revision->markdown = $values->markdown;
		$revision->title = $values->title;
		$revision->post = $post;
		$revision->content = $formatted['text'];
		$revision->user = $this->user->identity->id;
		$revision->ip = $this->context->getByType('Nette\Http\IRequest')->remoteAddress;
		$post->revisions->add($revision);

		$cache = new Cache($this->context->getByType('Nette\Caching\IStorage'), 'posts');
		$cache->save($post->id, $formatted['text']);

		$this->posts->persistAndFlush($post);

		$this->flashMessage('Aktuálka byla odeslána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function postFormPreview(SubmitButton $button) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->form->values;

		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->template->preview = $formatted['text'];

		$this->flashMessage('Toto je jen náhled, aktuálka zatím nebyla uložena.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate() {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('post', $this->action)) {
			$this->error('Pro vytváření příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}

	public function actionEdit($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('post', $this->action)) {
			$this->error('Pro úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$post = $this->posts->getById($id);
		if (!$post) {
			$this->error('Aktuálka nenalezena');
		}

		$data = [];
		$data['title'] = $post->title;
		$data['markdown'] = $post->markdown;
		$this['postForm']->setDefaults($data);
	}

	public function renderRss() {
		$posts = $this->posts->findAll()->orderBy(['createdAt' => 'DESC'])->limitBy(15);
		$this->template->posts = $posts;
	}
}
