<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;
use Override;

class ChatQuote extends AbstractBlock {
	public function __construct(
		/** ID of the quoted chat message */
		private readonly int $id,
	) {
	}

	/**
	 * Returns the ID of the quoted chat message.
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Returns true if this block can contain the given block as a child node.
	 */
	#[Override]
	public function canContain(AbstractBlock $block): bool {
		return false;
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
		return false;
	}
}
