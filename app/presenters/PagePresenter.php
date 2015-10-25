<?php
namespace App\Presenters;

use Nette;
use Nette\Caching\Cache;
use Nette\Forms\Controls\SubmitButton;
use Nextras\Forms\Rendering;
use Tracy\Debugger;
use App;
use App\Model;

class PagePresenter extends BasePresenter {
	/** @var Model\Formatter @inject */
	public $formatter;

	/** @var Model\PageRepository @inject */
	public $pages;

	/** @var Model\UserRepository @inject */
	public $users;

	public function renderShow($slug) {
		$page = $this->pages->getBy(['slug' => $slug]);
		if (!$page) {
			if ($this->allowed('page', 'create')) {
				$httpResponse = $this->context->getByType('Nette\Http\Response');
				$httpResponse->setCode(Nette\Http\Response::S404_NOT_FOUND);
				$this->template->slug = $slug;
				$this->setView('@no-page');
				$this->sendTemplate();
			} else {
				$this->error('Stránka nenalezena');
			}
		}

		if (isset($page->redirect)) {
			$this->redirectUrl($page->redirect);
		}

		$cache = new Cache($this->context->getByType('Nette\Caching\IStorage'), 'pages');

		$this->template->page = $page;
		$this->template->content = $cache->load($page->slug, function() use ($page) {
			$formatted = $this->formatter->format($page->markdown);

			if (count($formatted['errors'])) {
				Debugger::log($this->formatter->formatErrors($formatted['errors']));
			}

			return $formatted['text'];
		});
	}

	public function actionPurge($id) {
		$page = $this->pages->getByID($id);
		if (!$page) {
			$this->error('Stránka nenalezena');
		}

		if (!$this->allowed($page, 'purge')) {
			$this->error('Nemáš právo vymazat cache!', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$cache = new Cache($this->context->getByType('Nette\Caching\IStorage'), 'pages');
		$cache->remove($page->slug);

		$this->flashMessage('Cache byla vymazána.', 'success');
		$this->redirect('show', $page->slug);
	}

	public function renderList() {
		$pages = $this->pages->findAll();
		$this->template->pages = $pages;
	}

	public function renderLinks() {
		$slugs = $this->pages->findAll()->fetchPairs(null, 'slug');
		$pages = $this->pages->findAll();
		$pagesJson = [];

		foreach ($pages as $page) {
			$last_revision = $page->lastRevision;
			preg_match_all('~<a[^>]* href="(?:http://(?:www\.)fan-club-penguin.cz)?/([^"]+)\.html(?:#[^"]+)?"[^>]*>~', $last_revision->content, $links, PREG_PATTERN_ORDER);
			$links = array_unique($links[1]);
			$links = array_filter($links, function($item) use ($slugs) {
				if (in_array($item, $slugs)) {
					return true;
				}
			});
			$pagesJson[] = array('slug' => $page->slug, 'title' => $page->title, 'links' => $links, 'path' => $this->link('show', ['slug' => $page->slug]));
		}

		$this->template->pages = $pagesJson;
	}

	protected function createComponentPageForm() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired()->getControlPrototype()->autofocus = true;
		$form->addText('slug', 'Adresa:')->setRequired()->setType('url');
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15)->addClass('editor');

		$previewButton = $form->addSubmit('preview', 'Náhled');
		$previewButton->onClick[] = $this->pageFormPreview;
		$previewButton->getControlPrototype()->addClass('ajax');

		$submitButton = $form->addSubmit('send', 'Odeslat a zveřejnit');
		$submitButton->onClick[] = $this->pageFormSucceeded;
		$form->renderer->primaryButton = $submitButton;

		return $form;
	}

	public function pageFormSucceeded(SubmitButton $button) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->form->values;

		if ($this->action === 'create') {
			$page = new Model\Page;
		} else {
			$id = $this->getParameter('id');
			$page = $this->pages->getById($id);
			if (!$page) {
				$this->error('Stránka nenalezena.');
			}
		}

		if (!$this->allowed($this->action === 'create' ? 'page' : $page, $this->action)) {
			$this->error('Pro vytváření či úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$page->slug = $values->slug;
		$page->title = $values->title;
		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		if ($this->action === 'create') {
			$page->user = $this->users->getById($this->user->identity->id);
		}

		try {
			$this->pages->persistAndFlush($page);

			$revision = new Model\Revision;
			$revision->markdown = $values->markdown;
			$revision->page = $page;
			$revision->content = $formatted['text'];
			$revision->user = $this->users->getById($this->user->identity->id);
			$revision->ip = $this->context->getByType('Nette\Http\IRequest')->remoteAddress;
			$page->revisions->add($revision);

			$this->pages->persistAndFlush($page);

			$cache = new Cache($this->context->getByType('Nette\Caching\IStorage'), 'pages');
			$cache->save($page->slug, $formatted['text']);

			$this->flashMessage('Stránka byla odeslána.', 'success');
			$this->redirect('show', $values->slug);
		} catch (\Nextras\Dbal\UniqueConstraintViolationException $e) {
			$this->flashMessage('Stránka s tímto slugem již existuje.', 'danger');
		}
	}

	public function pageFormPreview(SubmitButton $button) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$values = $button->form->values;

		$formatted = $this->formatter->format($values['markdown']);

		if (count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->template->preview = $formatted['text'];

		$this->flashMessage('Toto je jen náhled, stránka zatím nebyla uložena.', 'info');

		$this->redrawControl('flashes');
		$this->redrawControl('preview');
	}

	public function actionCreate($slug = null) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if (!$this->allowed('page', $this->action)) {
			$this->error('Pro vytváření stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		if (isset($slug)) {
			$this['pageForm']['slug']->defaultValue = $slug;
		}
	}

	public function actionEdit($id) {
		if (!$this->user->loggedIn) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		$page = $this->pages->getById($id);
		if (!$page) {
			$this->error('Stránka nenalezena');
		}
		if (!$this->allowed($page, $this->action)) {
			$this->error('Pro úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}

		$data = $page->toArray();
		$data['markdown'] = $page->markdown;
		$this['pageForm']->setDefaults($data);
	}

	public function renderHistory($id) {
		$page = $this->pages->getById($id);
		if (!$page) {
			$this->error('Stránka nenalezena.');
		}
		$this->template->page = $page;
		$this->template->revisions = $page->revisions->get()->orderBy(['timestamp' => 'DESC']);
	}
}
