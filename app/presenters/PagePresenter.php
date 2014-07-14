<?php
namespace App\Presenters;

use Nette, Nextras\Forms\Rendering;


class PagePresenter extends BasePresenter {
	/** @var \Parsedown @inject */
	public $parsedown;
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
		if(!$this->user->isInRole('admin')) {
			$this->error('You need to log in to create or edit pages');
		}
		$values = $form->getValues();
		$values['content'] = $this->parsedown->parse($values['markdown']);
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
		if(!$this->user->isInRole('admin')) {
			$this->redirect('Sign:in');
		}
	}
	
	public function actionEdit($slug) {
		if(!$this->user->isInRole('admin')) {
			$this->redirect('Sign:in');
		}
		$page = $this->database->table('page')->where('slug', $slug)->fetch();
		if(!$page) {
			$this->error('Stránka nenalezena');
		}
		$data = $page->toArray();
		$this['pageForm']->setDefaults($data);
	}
}
