<?php
namespace App\Presenters;

use Nette;
use Nextras\Forms\Rendering;

class PagePresenter extends BasePresenter {
	/** @var \App\Model\Formatter @inject */
	public $formatter;

	/** @var Nette\Database\Context @inject */
	public $database;

	public function renderShow($slug) {
		$page = $this->database->table('page')->where('slug', $slug)->fetch();
		if(!$page) {
			$this->error('Stránka nenalezena');
		}
		$last_revision = $page->related('page_revision')->order('timestamp DESC')->fetch();
		if(!$last_revision) {
			$this->error('Stránka nemá žádné revize.');
		}

		$page = $page->toArray();
		$page['content'] = $last_revision->content;
		$page['user'] = $last_revision->user;
		$page['timestamp'] = $last_revision->timestamp;

		$this->template->page = $page;
	}

	public function renderList() {
		$pages = $this->database->table('page');
		$this->template->pages = $pages;
	}

	public function renderLinks() {
		$slugs = $this->database->table('page')->fetchPairs(null, 'slug');
		$pages = $this->database->table('page');
		$pagesJson = [];

		foreach($pages as $page) {
			$last_revision = $page->related('page_revision')->order('timestamp DESC')->fetch();
			preg_match_all('~<a[^>]* href="(?:http://(?:www\.)fan-club-penguin.cz)?/([^"]+)\.html(?:#[^"]+)?"[^>]*>~', $last_revision->content, $links, PREG_PATTERN_ORDER);
			$links = array_unique($links[1]);
			$links = array_filter($links, function($item) use ($slugs) {
				if(in_array($item, $slugs)) {
					return true;
				}
			});
			$pagesJson[] = array('slug' => $page->slug, 'title' => $page->title, 'links' => $links);
		}

		$this->template->pages = $pagesJson;
	}

	protected function createComponentPageForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new Rendering\Bs3FormRenderer);
		$form->addText('title', 'Nadpis:')->setRequired();
		$form->addText('slug', 'Adresa:')->setRequired()->setType('url');
		$form->addTextArea('markdown', 'Obsah:')->setRequired()->getControlPrototype()->addRows(15);

		$form->addSubmit('send', 'Odeslat a zveřejnit');
		$form->onSuccess[] = $this->pageFormSucceeded;

		return $form;
	}
	
	public function pageFormSucceeded(Nette\Application\UI\Form $form) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro vytváření či úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();
		$id = $this->getParameter('id');
		
		if($id) {
			$page = $this->database->table('page')->get($id);
			$page->update([
				'slug' => $values['slug'],
				'title' => $values['title']
			]);
		} else {
			$values['user_id'] = $this->user->identity->id;
			$page = $this->database->table('page')->insert([
				'slug' => $values['slug'],
				'title' => $values['title'],
				'user_id' => $this->user->identity->id
			]);
		}
		$formatted = $this->formatter->format($values['markdown']);

		if(count($formatted['errors'])) {
			$this->flashMessage($this->formatter->formatErrors($formatted['errors']), 'warning');
		}

		$this->database->table('page_revision')->insert([
			'page_id' => $page['id'],
			'markdown' => $values['markdown'],
			'content' => $formatted['text'],
			'user_id' => $this->user->identity->id,
			'ip' => $this->context->httpRequest->remoteAddress
		]);

		$this->flashMessage('Stránka byla odeslána.', 'success');
		$this->redirect('show', $values->slug);
	}

	public function actionCreate() {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro vytváření stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}
	
	public function actionEdit($id) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$page = $this->database->table('page')->get($id);
		if(!$page) {
			$this->error('Stránka nenalezena');
		}
		$last_revision = $page->related('page_revision')->order('timestamp', 'desc')->fetch();
		if(!$last_revision) {
			$this->error('Stránka nemá žádné revize.');
		}

		$data = $page->toArray();
		$data['markdown'] = $last_revision->markdown;
		$this['pageForm']->setDefaults($data);
	}

	public function renderHistory($id) {
		$page = $this->database->table('page')->get($id);
		if(!$page) {
			$this->error('Stránka nenalezena.');
		}
		$this->template->page = $page;
		$this->template->revisions = $page->related('page_revision')->order('timestamp', 'desc');
	}
}
