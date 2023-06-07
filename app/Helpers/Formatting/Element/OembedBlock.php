<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use Alb\OEmbed\Response;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Cursor;

class OembedBlock extends AbstractBlock {
	public function __construct(
		/** @param Response of the OEmbed provider */
		private Response $response,
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
