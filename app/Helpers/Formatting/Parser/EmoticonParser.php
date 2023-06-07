<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

class EmoticonParser implements InlineParserInterface {
	/** @var string[] */
	private array $characters;

	private string $regex;

	public function __construct(
		/** @param array[] $images */
		private array $images,
		/** @param string[] $emoticons */
		private array $emoticons,
	) {
		$this->characters = array_unique(array_map(fn($emoticon) => mb_substr($emoticon, 0, 1), array_keys($emoticons)));

		$this->regex = '(^(' . implode('|', array_map(fn($emoticon) => preg_quote($emoticon), array_keys($emoticons))) . '))';
	}

	public function getCharacters(): array {
		return $this->characters;
	}

	public function parse(InlineParserContext $inlineContext): bool {
		$cursor = $inlineContext->getCursor();

		$previousState = $cursor->saveState();
		$emoticon = $cursor->match($this->regex);

		if ($emoticon === null) {
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
