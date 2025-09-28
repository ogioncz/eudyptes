<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;
use Override;

class Spoiler extends AbstractBlock {
	public function __construct(
		/** Summary of the spoiler block */
		private readonly ?string $summary = null,
	) {
	}

	/**
	 * Returns the summary of the spoiler block.
	 */
	public function getSummary(): ?string {
		return $this->summary;
	}

	/**
	 * Returns true if this block can contain the given block as a child node.
	 */
	#[Override]
	public function canContain(AbstractBlock $block): bool {
		return true;
	}

	/**
	 * Whether this is a code block.
	 */
	#[Override]
	public function isCode(): bool {
		return false;
	}

	#[Override]
	public function matchesNextLine(Cursor $cursor): bool {
		return true;
	}
}
