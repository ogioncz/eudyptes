<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Node\Block\AbstractBlock;

class ChatQuote extends AbstractBlock {
	public function __construct(
		/** ID of the quoted chat message */
		private readonly int $id,
	) {
		parent::__construct();
	}

	/**
	 * Returns the ID of the quoted chat message.
	 */
	public function getId(): int {
		return $this->id;
	}
}
