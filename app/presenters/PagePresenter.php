<?php
namespace App\Presenters;

use Nette, Nextras\Forms\Rendering;


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

		$this->template->page = $page;
	}

	public function renderLinks() {
		$slugs = $this->database->table('page')->fetchPairs(null, 'slug');
		$pages = $this->database->table('page');
		foreach($pages as $page) {
			preg_match_all('~<a[^>]* href="(?:http://(?:www\.)fan-club-penguin.cz)?/([^"]+)\.html(?:#[^"]+)?"[^>]*>~', $page->content, $links, PREG_PATTERN_ORDER);
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
	
	public function pageFormSucceeded($form) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro vytváření či úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$values = $form->getValues();
		$values['content'] = $this->formatter->format($values['markdown']);
		$slug = $this->getParameter('slug');
		
		if($slug) {
			$page = $this->database->table('page')->where('slug', $slug);
			$page->update($values);
		} else {
			$values['user_id'] = $this->user->identity->id;
			$page = $this->database->table('page')->insert($values);
		}

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
	
	public function actionEdit($slug) {
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
		if(!$this->user->isInRole('admin')) {
			$this->error('Pro úpravu stránek musíš mít oprávnění.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$page = $this->database->table('page')->where('slug', $slug)->fetch();
		if(!$page) {
			$this->error('Stránka nenalezena');
		}
		$data = $page->toArray();
		$this['pageForm']->setDefaults($data);
	}
}
