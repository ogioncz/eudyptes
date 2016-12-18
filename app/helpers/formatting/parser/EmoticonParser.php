<?php

namespace App\Helpers\Formatting\Parser;

use League\CommonMark\Inline\Parser\AbstractInlineParser;
use League\CommonMark\InlineParserContext;
use League\CommonMark\Inline\Element\Image;


class EmoticonParser extends AbstractInlineParser {
	/**
	 * @var array
	 */
	private $images;

	/**
	 * @var array
	 */
	private $emoticons;

	/**
	 * Constructor
	 *
	 * @param array $images
	 * @param array $emoticons
	 */
	public function __construct($images, $emoticons) {
		$this->images = $images;
		$this->emoticons = $emoticons;

		$this->characters = array_unique(array_map(function($emoticon) {
			return mb_substr($emoticon, 0, 1);
		}, array_keys($emoticons)));

		$this->regex = '(^(' . implode('|', array_map(function($emoticon) {
			return preg_quote($emoticon);
		}, array_keys($emoticons))) . '))';
	}

	public function getCharacters() {
		return $this->characters;
	}

	public function parse(InlineParserContext $inlineContext) {
		$cursor = $inlineContext->getCursor();

		$previousState = $cursor->saveState();
		$emoticon = $cursor->match($this->regex);
		\Tracy\Debugger::barDump($emoticon);

		if (is_null($emoticon)) {
			$cursor->restoreState($previousState);
			return false;
		}

		$data = $this->images[$this->emoticons[$emoticon]];
		$img = new Image($data['src']);
		$img->data['attributes'] = $data;
		$inlineContext->getContainer()->appendChild($img);

		return true;
	}
}
