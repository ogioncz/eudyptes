<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\OembedBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use Override;

class OembedContinueParser extends AbstractBlockContinueParser {
	public function __construct(
		private readonly OembedBlock $block
	) {
	}

	#[Override]
	public function getBlock(): OembedBlock {
		return $this->block;
	}

	#[Override]
	public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue {
		return BlockContinue::none();
	}
}
