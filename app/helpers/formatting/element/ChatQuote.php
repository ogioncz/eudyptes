<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;

class ChatQuote extends AbstractBlock {
	public function __construct(
		/** @param ID of the quoted chat message */
		private int $id,
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
	public function canContain(AbstractBlock $block): bool {
		return false;
	}

	/**
	 * Whether this is a code block.
	 */
	public function isCode(): bool {
		return false;
	}

	public function matchesNextLine(Cursor $cursor): bool {
		return false;
	}
}
