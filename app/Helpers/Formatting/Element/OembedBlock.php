<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use Cohensive\OEmbed\Embed;
use League\CommonMark\Node\Block\AbstractBlock;

class OembedBlock extends AbstractBlock {
	public function __construct(
		/** Embedded content returned by the OEmbed provider */
		private readonly Embed $embed,
	) {
		parent::__construct();
	}

	/**
	 * Returns the embedded content returned by the OEmbed provider.
	 */
	public function getEmbed(): Embed {
		return $this->embed;
	}
}
