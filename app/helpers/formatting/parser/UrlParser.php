<?php

namespace App\Helpers\Formatting\Parser;

use League\CommonMark\Inline\Parser\AbstractInlineParser;
use League\CommonMark\InlineParserContext;
use League\CommonMark\Inline\Element\Link;


class UrlParser extends AbstractInlineParser {
	public function getCharacters() {
		return ['h'];
	}

	public function parse(InlineParserContext $inlineContext) {
		$cursor = $inlineContext->getCursor();

		$previous = $cursor->peek(-1);
		if ($previous !== null && !in_array($previous, [' ', '('])) {
			return false;
		}

		$previousState = $cursor->saveState();
		$url = $cursor->match(self::getUrlRegex());

		if (is_null($url)) {
			$cursor->restoreState($previousState);
			return false;
		}

		$link = new Link($url, $url);
		$inlineContext->getContainer()->appendChild($link);

		return true;
	}

	private static function getUrlRegex() {
		$alphaRegex = "a-z\x80-\xFF";
		$domainRegex = "[0-9$alphaRegex](?:[-0-9$alphaRegex]{0,61}[0-9$alphaRegex])?";
		$topDomainRegex = "[$alphaRegex][-0-9$alphaRegex]{0,17}[$alphaRegex]";
		$urlRegex = "(^https?://((?:$domainRegex\\.)*$topDomainRegex|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?\\b)i";
		return $urlRegex;
	}
}
