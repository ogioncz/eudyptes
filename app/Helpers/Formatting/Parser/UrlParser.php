<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Override;

class UrlParser implements InlineParserInterface {
	#[Override]
	public function getMatchDefinition(): InlineParserMatch {
		return InlineParserMatch::regex(self::getUrlRegex());
	}

	#[Override]
	public function parse(InlineParserContext $inlineContext): bool {
		$cursor = $inlineContext->getCursor();

		$previous = $cursor->peek(-1);
		if ($previous !== null && !\in_array($previous, [' ', '('], true)) {
			return false;
		}

		$url = $inlineContext->getFullMatch();
		$cursor->advanceBy($inlineContext->getFullMatchLength());

		$link = new Link($url, $url);
		$inlineContext->getContainer()->appendChild($link);

		return true;
	}

	private static function getUrlRegex(): string {
		$alphaRegex = "a-z\x80-\xFF";
		$domainRegex = "[0-9$alphaRegex](?:[-0-9$alphaRegex]{0,61}[0-9$alphaRegex])?";
		$topDomainRegex = "[$alphaRegex][-0-9$alphaRegex]{0,17}[$alphaRegex]";
		$urlRegex = "https?:\\/\\/((?:$domainRegex\\.)*$topDomainRegex|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(\\/\\S*)?\\b";

		return $urlRegex;
	}
}
