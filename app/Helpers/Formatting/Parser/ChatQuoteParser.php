<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\ChatQuote;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use Override;

class ChatQuoteParser implements BlockParserInterface {
	private static string $regex = '(^\{#([0-9]+)\}$)';

	#[Override]
	public function parse(ContextInterface $context, Cursor $cursor): bool {
		if ($cursor->isIndented()) {
			return false;
		}

		$previousState = $cursor->saveState();
		$quoteBlock = $cursor->match(self::$regex);

		if ($quoteBlock === null) {
			$cursor->restoreState($previousState);

			return false;
		}

		$context->getContainer()->appendChild(new ChatQuote(self::getQuotedId($quoteBlock)));

		return true;
	}

	private static function getQuotedId($quote): int {
		preg_match(self::$regex, (string) $quote, $match);

		return (int) $match[1];
	}
}
