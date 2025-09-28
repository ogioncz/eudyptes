<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Node\Block\AbstractBlock;

class Spoiler extends AbstractBlock {
	public function __construct(
		/** Summary of the spoiler block */
		private readonly ?string $summary = null,
	) {
		parent::__construct();
	}

	/**
	 * Returns the summary of the spoiler block.
	 */
	public function getSummary(): ?string {
		return $this->summary;
	}
}
