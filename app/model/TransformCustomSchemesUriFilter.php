<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Application\Application;

class TransformCustomSchemesUriFilter extends \HTMLPurifier_URIFilter {
	/** * @var string */
	public $name = 'TransformCustomSchemes';

	public function __construct(protected Application $app) {
	}

	/**
	 * @param \HTMLPurifier_URI $uri
	 * @param \HTMLPurifier_Config $config
	 * @param \HTMLPurifier_Context $context
	 */
	public function filter(&$uri, $config, $context): bool {
		if ($uri->scheme !== 'post' && $uri->scheme !== 'page') {
			return true;
		}

		$uri->scheme = ucfirst($uri->scheme);
		$uri = new \HTMLPurifier_URI(null, null, null, null, $this->app->getPresenter()->link($uri->scheme . ':show', $uri->path), $uri->query, $uri->fragment);

		return true;
	}
}
