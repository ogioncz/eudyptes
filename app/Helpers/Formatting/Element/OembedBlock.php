<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use Cohensive\OEmbed\Embed;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;
use Override;

class OembedBlock extends AbstractBlock {
	public function __construct(
		/** @param Embedded content returned by the OEmbed provider */
		private readonly Embed $embed,
	) {
	}

	/**
	 * Returns the embedded content returned by the OEmbed provider.
	 */
	public function getEmbed(): Embed {
		return $this->embed;
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
