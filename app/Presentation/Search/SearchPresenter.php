<?php

declare(strict_types=1);

namespace App\Presentation\Search;

use App\Presentation\BasePresenter;
use Indextank_Api;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;

/**
 * SearchPresenter shows search results.
 */
class SearchPresenter extends BasePresenter {
	#[Inject]
	public Container $context;

	private int $itemsPerPage = 15;

	public function renderResult($query): void {
		if ($query) {
			$paginator = $this['paginator']->getPaginator();
			$page = $paginator->page;
			$paginator->itemsPerPage = $this->itemsPerPage;

			$client = new Indextank_Api($this->context->getParameter('indextank'));
			$index = $client->get_index('web');

			$fetch_fields = 'title,timestamp';
			$snippet_fields = 'text';
			$response = $index->search($query, ($page - 1) * $this->itemsPerPage, $this->itemsPerPage, null, $snippet_fields, $fetch_fields);

			if ($response->matches == 0) {
				$this->flashMessage('Nebyl nalezen žádný výsledek.', 'danger');
			} elseif ($response->matches == 1) {
				$this->flashMessage('Byl nalezen jeden výsledek.', 'success');
			} elseif ($response->matches < 5) {
				$this->flashMessage('Byly nalezeny ' . $response->matches . ' výsledky.', 'success');
			} else {
				$this->flashMessage('Bylo nalezeno ' . $response->matches . ' výsledků.', 'success');
			}

			$paginator->itemCount = $response->matches;
			$this->getTemplate()->query = $query;
			$this->getTemplate()->results = $response->results;
		}
	}
}
