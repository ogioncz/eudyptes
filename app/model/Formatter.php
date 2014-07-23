<?php

namespace App\Model;

class Formatter extends \Nette\Object {
	/** @var \Parsedown */
	public $parsedown;
	/** @var \HTMLPurifier */
	public $purifier;

	public function __construct(\Parsedown $parsedown, \HTMLPurifier $purifier) {
		$this->parsedown = $parsedown;
		$this->purifier = $purifier;
	}

	public function format($text) {
		$text = $this->parsedown->parse($text);

		$text = $this->purifier->purify($text);

		return $text;
	}
	
}
