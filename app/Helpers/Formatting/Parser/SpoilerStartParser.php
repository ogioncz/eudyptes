<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\Spoiler;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Override;

class SpoilerStartParser implements BlockStartParserInterface {
	#[Override]
	public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart {
		if ($cursor->isIndented()) {
			return BlockStart::none();
		}

		$match = $cursor->match('/^¡¡¡(\s.*)?$/');
		if ($match === null) {
			return BlockStart::none();
		}

		$summary = trim(mb_substr($match, mb_strlen('¡¡¡')));
		$block = new Spoiler($summary !== '' ? $summary : null);

		return BlockStart::of(new SpoilerParser($block))->at($cursor);
	}
}
