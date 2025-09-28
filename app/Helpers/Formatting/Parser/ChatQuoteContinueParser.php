<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\ChatQuote;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use Override;

class ChatQuoteContinueParser extends AbstractBlockContinueParser {
	public function __construct(
		private readonly ChatQuote $block
	) {
	}

	#[Override]
	public function getBlock(): ChatQuote {
		return $this->block;
	}

	#[Override]
	public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue {
		return BlockContinue::none();
	}
}
