<?php

namespace App\Helpers\Formatting\Parser;

use Alb\OEmbed;
use App\Helpers\Formatting\Element\OembedBlock;
use League\CommonMark\Block\Parser\AbstractBlockParser;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Element\Link;


class OembedParser extends AbstractBlockParser {
	/**
	* @var array
	*/
	private $whitelistedDomains;

	/** @var OEmbed\Simple */
	private $oembed;

	/**
	 * Constructor
	 *
	 * @param OEmbed\Simple $oembed
	 * @param array $whitelistedDomains
	 */
	public function __construct($oembed, $whitelistedDomains) {
		$this->oembed = $oembed;
		$this->whitelistedDomains = $whitelistedDomains;
	}

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
		$url = $cursor->match(self::getUrlRegex());

		if (is_null($url)) {
			$cursor->restoreState($previousState);
			return false;
		}

		if (in_array(self::getDomain($url), $this->whitelistedDomains)) {
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

	private static function getUrlRegex() {
		$alphaRegex = "a-z\x80-\xFF";
		$domainRegex = "[0-9$alphaRegex](?:[-0-9$alphaRegex]{0,61}[0-9$alphaRegex])?";
		$topDomainRegex = "[$alphaRegex][-0-9$alphaRegex]{0,17}[$alphaRegex]";
		$urlRegex = "(^https?://((?:$domainRegex\\.)*$topDomainRegex|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?$)i";
		return $urlRegex;
	}
}
