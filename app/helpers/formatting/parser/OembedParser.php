<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use Alb\OEmbed;
use App\Helpers\Formatting\Element\OembedBlock;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use League\CommonMark\Inline\Element\Link;

class OembedParser implements BlockParserInterface {
	public function __construct(
		private OEmbed\Simple $oembed,
		/** @param string[] */
		private array $whitelistedDomains,
	) {
	}

	public function parse(ContextInterface $context, Cursor $cursor): bool {
		if ($cursor->isIndented()) {
			return false;
		}

		$previousState = $cursor->saveState();
		$url = $cursor->match(self::getUrlRegex());

		if ($url === null) {
			$cursor->restoreState($previousState);
			return false;
		}

		if (\in_array(self::getDomain($url), $this->whitelistedDomains, true)) {
			try {
				$response = $this->oembed->request($url);
				if ($response) {
					$context->addBlock(new OembedBlock($response));
					return true;
				}
			} catch (\Exception $e) {
				\Tracy\Debugger::log($e);
			} // can’t serve, link is better than nothing so let’s leave it at that
		}

		$cursor->restoreState($previousState);
		return false;
	}

	private static function getDomain($url) {
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
