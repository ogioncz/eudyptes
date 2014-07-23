<?php

namespace App\Model;

use Alb\OEmbed;

class Formatter extends \Nette\Object {
	public static $OEMBED_WHITELIST = ['www.youtube.com'];

	/** @var \Parsedown */
	public $parsedown;

	/** @var \HTMLPurifier */
	public $purifier;

	/** @var OEmbed\Simple */
	public $oembed;

	private $replacements = [];

	public function __construct(\Parsedown $parsedown, \HTMLPurifier $purifier, OEmbed\Simple $oembed) {
		$this->parsedown = $parsedown;
		$this->purifier = $purifier;
		$this->oembed = $oembed;
	}

	public function format($text) {
		$alpha = "a-z\x80-\xFF";
		$domain = "[0-9$alpha](?:[-0-9$alpha]{0,61}[0-9$alpha])?";
		$topDomain = "[$alpha][-0-9$alpha]{0,17}[$alpha]";
		if(preg_match_all("(^https?://((?:$domain\\.)*$topDomain|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?$)im", $text, $matches, PREG_SET_ORDER)) {
			foreach($matches as $match) {
				if(in_array($match[1], Formatter::$OEMBED_WHITELIST) && !isset($this->replacements[$match[0]])) {
					try {
						$this->replacements[$match[0]] = '<figure>' . $this->oembed->request($match[0])->getHtml() . '</figure>';
					} catch(\Exception $e) {}
				}
			}
		}
		$text = strTr($text, $this->replacements);
	
		$text = $this->parsedown->parse($text);

		$text = $this->purifier->purify($text);

		return $text;
	}
	
}
