<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\Spoiler;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use Override;

class SpoilerParser implements BlockContinueParserInterface {
	private const string CLOSING_TOKEN = '!!!';

	public function __construct(
		private readonly Spoiler $block
	) {
	}

	#[Override]
	public function getBlock(): Spoiler {
		return $this->block;
	}

	#[Override]
	public function isContainer(): bool {
		return true;
	}

	#[Override]
	public function canContain(object $childBlock): bool {
		return true;
	}

	#[Override]
	public function canHaveLazyContinuationLines(): bool {
		return false;
	}

	#[Override]
	public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue {
		if (!$cursor->isIndented() && $cursor->getRemainder() === self::CLOSING_TOKEN && $this->isHandlingInnermostSpoilerBlock($activeBlockParser)) {
			$cursor->advanceToEnd();

			return BlockContinue::finished();
		}

		return BlockContinue::at($cursor);
	}

	#[Override]
	public function addLine(string $line): void {
	}

	#[Override]
	public function closeBlock(): void {
	}

	/**
	 * CommonMark calls `BlockContinueParserInterface::tryContinue()`
	 * starting with the document parser and going up the stack.
	 * If we want to close the innermost spoiler block, we should
	 * only call `BlockContinue::finished()` when we get to
	 * the current level in the stack.
	 */
	private function isHandlingInnermostSpoilerBlock(BlockContinueParserInterface $activeBlockParser): bool {
		$block = $activeBlockParser->getBlock();
		while (!$block instanceof Spoiler && $block !== null) {
			$block = $block->parent();
		}

		return $this->getBlock() === $block;
	}
}
