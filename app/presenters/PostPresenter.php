<?php
namespace App\Presenters;

use Nette;
use Nette\Caching\Cache;
use Nette\Forms\Controls\SubmitButton;
use Nextras\Forms\Rendering;
use App;
use App\Helpers\Formatting;
use App\Model\Post;
use App\Model\PostRevision;

/**
 * PostPresenter handles news posts.
 */
class PostPresenter extends BasePresenter {
	/** @var Formatting\Formatter @inject */
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

		$this->getTemplate()->post = $post;
	}

	public function actionPurge($id) {
		$post = $this->posts->getById($id);
		if (!$post) {
			$this->error('Aktuálka nenalezena');
		}

		if (!$this->allowed($post, 'purge')) {
			$this->error('Nemáš právo vymazat cache!', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$cache = new Cache($this->getContext()->getByType('Nette\Caching\IStorage'), 'posts');
		$cache->remove($post->id);

		$this->flashMessage('Cache byla vymazána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function renderList() {
		$posts = $this->posts->findAll()->orderBy(['createdAt' => 'DESC']);
		$this->getTemplate()->posts = $posts;
	}

	protected function createComponentPostForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');
		$form->addCheckbox('published', 'Zveřejnit')->setDefaultValue(true);

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = [$this, 'postFormPreview'];
		$previewButton->getControlPrototype()->addClass('ajax');

		$submitButton = $form->addSubmit('save', 'Uložit');
		$submitButton->onClick[] = [$this, 'postFormSucceeded'];
		$form->getRenderer()->primaryButton = $submitButton;

		return $form;
	}

	public function postFormSucceeded(SubmitButton $button) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		if ($this->getAction() === 'create') {
			$post = new Post;
		} else {
			$id = $this->getParameter('id');
			$post = $this->posts->getById($id);
			if (!$post) {
				$this->error('Aktuálka nenalezena.');
			}
		}

		if (!$this->allowed($this->getAction() === 'create' ? 'post' : $post, $this->getAction())) {
			$this->error('Pro vytváření či úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$post->published = $values->published;

		$formatted = $this->formatter->format($values->markdown);
		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		if ($this->getAction() === 'create') {
			$post->user = $this->users->getById($this->getUser()->getIdentity()->id);
		}

		$this->posts->persistAndFlush($post);

		$revision = new PostRevision;
		$revision->markdown = $values->markdown;
		$revision->title = $values->title;
		$revision->post = $post;
		$revision->content = $formatted['text'];
		$revision->user = $this->getUser()->getIdentity()->id;
		$revision->ip = $this->getContext()->getByType('Nette\Http\IRequest')->remoteAddress;
		$post->revisions->add($revision);

		$cache = new Cache($this->getContext()->getByType('Nette\Caching\IStorage'), 'posts');
		$cache->save($post->id, $formatted['text']);

		$this->posts->persistAndFlush($post);

		$this->flashMessage('Aktuálka byla odeslána.', 'success');
		$this->redirect('show', $post->id);
	}

	public function postFormPreview(SubmitButton $button) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->getTemplate()->preview = $formatted['text'];

		$this->flashMessage('Toto je jen náhled, aktuálka zatím nebyla uložena.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate() {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('post', $this->getAction())) {
			$this->error('Pro vytváření příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}

	public function actionEdit($id) {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('post', $this->getAction())) {
			$this->error('Pro úpravu příspěvků musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$post = $this->posts->getById($id);
		if (!$post) {
			$this->error('Aktuálka nenalezena');
		}

		$data = [];
		$data['title'] = $post->title;
		$data['markdown'] = $post->markdown;
		$data['published'] = $post->published;
		$this['postForm']->setDefaults($data);
	}

	public function renderRss() {
		$posts = $this->posts->findAll()->orderBy(['createdAt' => 'DESC'])->limitBy(15);
		$this->getTemplate()->posts = $posts;
	}
}
