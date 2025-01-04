<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use Alb\OEmbed\Response;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;
use Override;

class OembedBlock extends AbstractBlock {
	public function __construct(
		/** @param Response of the OEmbed provider */
		private readonly Response $response,
	) {
	}

	/**
	 * Returns the response of the OEmbed provider.
	 */
	public function getResponse(): Response {
		return $this->response;
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
