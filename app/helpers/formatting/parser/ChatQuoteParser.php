<?php

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\ChatQuote;
use League\CommonMark\Block\Parser\AbstractBlockParser;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use League\CommonMark\InlineParserContext;


class ChatQuoteParser extends AbstractBlockParser {
	private static $regex = '(^\{#([0-9]+)\}$)';

	/**
	 * @param ContextInterface $context
	 * @param Cursor $cursor
	 *
	 * @return bool
	 */
	public function parse(ContextInterface $context, Cursor $cursor) {
		if ($cursor->isIndented()) {
			return false;
		}

		$previousState = $cursor->saveState();
		$quoteBlock = $cursor->match(self::$regex);

		if (is_null($quoteBlock)) {
			$cursor->restoreState($previousState);
			return false;
		}

		$context->getContainer()->appendChild(new ChatQuote(self::getQuotedId($quoteBlock)));
		return true;
	}

	private static function getQuotedId($quote) {
		preg_match(self::$regex, $quote, $match);
		return $match[1];
	}
}
