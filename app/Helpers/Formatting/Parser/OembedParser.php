<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\OembedBlock;
use Cohensive\OEmbed\Factory as OEmbedFactory;
use Exception;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Override;
use Tracy\Debugger;

class OembedParser implements BlockStartParserInterface {
	public function __construct(
		private readonly OEmbedFactory $oembed,
		/** @var string[] */
		private readonly array $whitelistedDomains,
	) {
	}

	#[Override]
	public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart {
		if ($cursor->isIndented()) {
			return BlockStart::none();
		}

		$url = $cursor->match(self::getUrlRegex());

		if ($url === null) {
			return BlockStart::none();
		}

		if (\in_array(self::getDomain($url), $this->whitelistedDomains, true)) {
			try {
				$embed = $this->oembed->get($url);
				if ($embed !== null) {
					$block = new OembedBlock($embed);

					return BlockStart::of(new OembedContinueParser($block))->at($cursor);
				}
			} catch (Exception $e) {
				Debugger::log($e);
			} // can’t serve, link is better than nothing so let’s leave it at that
		}

		return BlockStart::none();
	}

	private static function getDomain(string $url): string {
		preg_match(self::getUrlRegex(), $url, $match);

		return $match[1];
	}

	private static function getUrlRegex(): string {
		$alphaRegex = "a-z\x80-\xFF";
		$domainRegex = "[0-9$alphaRegex](?:[-0-9$alphaRegex]{0,61}[0-9$alphaRegex])?";
		$topDomainRegex = "[$alphaRegex][-0-9$alphaRegex]{0,17}[$alphaRegex]";
		$urlRegex = "(^https?://((?:$domainRegex\\.)*$topDomainRegex|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?$)i";

		return $urlRegex;
	}
}
