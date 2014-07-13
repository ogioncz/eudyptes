<?php

namespace App\Presenters;

use Nette, App\Model;


/**
 * Search presenter.
 */
class SearchPresenter extends BasePresenter {
	/** @var Nette\Database\Context @inject */
	public $database;

	private $items_per_page = 15;

	public function renderResult($query) {
		if($query) {
			$paginator = $this["paginator"]->getPaginator();
			$page = $paginator->page;
			$paginator->itemsPerPage = $this->items_per_page;

			$client = new \Indextank_Api($this->context->parameters['indextank']);
			$index = $client->get_index('web');

			$fetch_fields = 'title,timestamp';
			$snippet_fields = 'text';
			$response = $index->search($query, ($page - 1) * $this->items_per_page, $this->items_per_page, null, $snippet_fields, $fetch_fields);
			
			if($response->matches == 0) {
				$this->flashMessage('Nebyl nalezen žádný výsledek.', 'error');
			} else if($response->matches == 1) {
				$this->flashMessage('Byl nalezen jeden výsledek.', 'success');
			} else if($response->matches < 5) {
				$this->flashMessage('Byly nalezeny ' . $response->matches . ' výsledky.', 'success');
			} else {
				$this->flashMessage('Bylo nalezeno ' . $response->matches . ' výsledků.', 'success');
			}

			$paginator->itemCount = $response->matches;
			$this->template->query = $query;
			$this->template->results = $response->results;
		}
	}
}