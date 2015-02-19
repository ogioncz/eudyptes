<?php

namespace App\Model;

use Nette;

class TransformCustomSchemesUriFilter extends \HTMLPurifier_URIFilter {
	/** * @var string */
	public $name = 'TransformCustomSchemes';

	/** @var Nette\Application\Application */
	protected $app;

	public function __construct(Nette\Application\Application $app) {
		$this->app = $app;
	}

	/**
	 * @param \HTMLPurifier_URI $uri
	 * @param \HTMLPurifier_Config $config
	 * @param \HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		if ($uri->scheme !== 'post' && $uri->scheme !== 'page') {
			return true;
		}

		$uri->scheme = ucfirst($uri->scheme);
		$uri = new \HTMLPurifier_URI(null, null, null, null, $this->app->presenter->link($uri->scheme . ':show', $uri->path), $uri->query, $uri->fragment);
		return true;
	}
}
