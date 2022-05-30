<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Helpers\Formatting;
use App\Model;
use Nette;
use Nette\Caching\Cache;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;
use Nextras\FormsRendering\Renderers;
use Tracy\Debugger;

/**
 * PagePresenter handles wiki articles.
 */
class PagePresenter extends BasePresenter {
	#[Nette\DI\Attributes\Inject]
	public Formatting\Formatter $formatter;

	#[Nette\DI\Attributes\Inject]
	public Model\PageRepository $pages;

	#[Nette\DI\Attributes\Inject]
	public Model\UserRepository $users;

	#[Nette\DI\Attributes\Inject]
	public Nette\Http\Response $response;

	#[Nette\DI\Attributes\Inject]
	public Nette\Caching\Storage $storage;

	public function renderShow($slug): void {
		$page = $this->pages->getBy(['slug' => $slug]);
		if (!$page) {
			if ($this->allowed('page', 'create')) {
				$httpResponse = $this->response;
				$httpResponse->setCode(Nette\Http\Response::S404_NOT_FOUND);
				$this->getTemplate()->slug = $slug;
				$this->setView('@no-page');
				$this->sendTemplate();
			} else {
				$this->error('Stránka nenalezena');
			}
		}

		if (isset($page->redirect)) {
			$this->redirectUrl($page->redirect);
		}

		$cache = new Cache($this->storage, 'pages');

		$this->getTemplate()->page = $page;
		$this->getTemplate()->content = $cache->load($page->slug, function() use ($page) {
			$formatted = $this->formatter->format($page->markdown);

			if (count($formatted['errors'])) {
				Debugger::log($this->formatter->formatErrors($formatted['errors']));
			}

			return $formatted['text'];
		});
	}

	public function actionPurge($id): void {
		$page = $this->pages->getById($id);
		if (!$page) {
			$this->error('Stránka nenalezena');
		}

		if (!$this->allowed($page, 'purge')) {
			$this->error('Nemáš právo vymazat cache!', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$cache = new Cache($this->storage, 'pages');
		$cache->remove($page->slug);

		$this->flashMessage('Cache byla vymazána.', 'success');
		$this->redirect('show', $page->slug);
	}

	public function renderList(): void {
		$pages = $this->pages->findAll();
		$this->getTemplate()->pages = $pages;
	}

	public function renderTitles(): void {
		$titles = [];

		$pages = $this->pages->findAll();
		foreach ($pages as $page) {
			if (isset($page->redirect) || $page->slug !== Strings::webalize($page->title, '/')) {
				continue;
			}

			$titles[] = $page->title;
		}

		$this->sendJson($titles);
	}

	public function renderLinks(): void {
		$slugs = $this->pages->findAll()->fetchPairs(null, 'slug');
		$pages = $this->pages->findAll();
		$pagesJson = [];

		foreach ($pages as $page) {
			$last_revision = $page->lastRevision;
			preg_match_all('~<a[^>]* href="(?:http://(?:www\.)fan-club-penguin.cz)?/([^"]+)\.html(?:#[^"]+)?"[^>]*>~', $last_revision->content, $links, \PREG_PATTERN_ORDER);
			$links = array_unique($links[1]);
			$links = array_filter($links, function($item) use ($slugs) {
				if (\in_array($item, $slugs, true)) {
					return true;
				}
			});
			$pagesJson[] = ['slug' => $page->slug, 'title' => $page->title, 'links' => $links, 'path' => $this->link('show', ['slug' => $page->slug])];
		}

		$this->getTemplate()->pages = $pagesJson;
	}

	protected function createComponentPageForm(): Nette\Application\UI\Form {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$renderer = new Renderers\Bs3FormRenderer();
		$form->setRenderer($renderer);
		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$slug = $form->addText('slug', 'Adresa:')->setRequired()->setType('url');
		if ($this->getAction() == 'edit') {
			$slug->setDisabled(true);
		}
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = [$this, 'pageFormPreview'];
		$previewButton->getControlPrototype()->addClass('ajax');

		$submitButton = $form->addSubmit('send', 'Odeslat a zveřejnit');
		$submitButton->onClick[] = [$this, 'pageFormSucceeded'];
		$renderer->primaryButton = $submitButton;

		return $form;
	}

	public function pageFormSucceeded(SubmitButton $button): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		if ($this->getAction() === 'create') {
			$page = new Model\Page;
		} else {
			$id = $this->getParameter('id');
			$page = $this->pages->getById($id);
			if (!$page) {
				$this->error('Stránka nenalezena.');
			}
		}

		if (!$this->allowed($this->getAction() === 'create' ? 'page' : $page, $this->getAction())) {
			$this->error('Pro vytváření či úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$page->title = $values->title;
		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		if ($this->getAction() === 'create') {
			$page->slug = $values->slug;
			$page->user = $this->users->getById($this->getUser()->getIdentity()->getId());
		}

		try {
			$this->pages->persistAndFlush($page);

			$revision = new Model\Revision;
			$revision->markdown = $values->markdown;
			$revision->page = $page;
			$revision->content = $formatted['text'];
			$revision->user = $this->users->getById($this->getUser()->getIdentity()->getId());
			$revision->ip = $this->getHttpRequest()->getRemoteAddress();
			$page->revisions->add($revision);

			$this->pages->persistAndFlush($page);

			$cache = new Cache($this->storage, 'pages');
			$cache->save($page->slug, $formatted['text']);

			$this->flashMessage('Stránka byla odeslána.', 'success');
			$this->redirect('show', $page->slug);
		} catch (\Nextras\Dbal\UniqueConstraintViolationException $e) {
			$this->flashMessage('Stránka s tímto slugem již existuje.', 'danger');
		}
	}

	public function pageFormPreview(SubmitButton $button): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->getForm()->getValues();

		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->getTemplate()->preview = $formatted['text'];

		$this->flashMessage('Toto je jen náhled, stránka zatím nebyla uložena.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate($slug = null): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('page', $this->getAction())) {
			$this->error('Pro vytváření stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		if (isset($slug)) {
			$this['pageForm']['slug']->defaultValue = $slug;
		}
	}

	public function actionEdit($id): void {
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$page = $this->pages->getById($id);
		if (!$page) {
			$this->error('Stránka nenalezena');
		}
		if (!$this->allowed($page, $this->getAction())) {
			$this->error('Pro úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$data = $page->toArray();
		$data['markdown'] = $page->markdown;
		$this['pageForm']->setDefaults($data);
	}

	public function renderHistory($id): void {
		$page = $this->pages->getById($id);
		if (!$page) {
			$this->error('Stránka nenalezena.');
		}
		$this->getTemplate()->page = $page;
		$this->getTemplate()->revisions = $page->revisions->get()->orderBy(['timestamp' => 'DESC']);
	}
}
