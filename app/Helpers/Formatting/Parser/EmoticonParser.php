<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Parser;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Override;

class EmoticonParser implements InlineParserInterface {
	public function __construct(
		/** @var array<string, array{src: string, alt: string, width: int, height: int}> */
		private array $images,
		/** @var array<string, string> */
		private array $emoticons,
	) {
	}

	#[Override]
	public function getMatchDefinition(): InlineParserMatch {
		return InlineParserMatch::oneOf(...array_keys($this->emoticons))->caseSensitive();
	}

	#[Override]
	public function parse(InlineParserContext $inlineContext): bool {
		$emoticon = $inlineContext->getFullMatch();

		$image = $this->emoticons[$emoticon] ?? null;
		if ($image === null) {
			return false;
		}

		$cursor = $inlineContext->getCursor();
		$cursor->advanceBy($inlineContext->getFullMatchLength());

		$data = $this->images[$image];
		$img = new Image($data['src']);
		['alt' => $alt, 'width' => $width, 'height' => $height] = $data;
		$attributes = ['alt' => $alt, 'width' => (string) $width, 'height' => (string) $height];
		$img->data->set('attributes', $attributes);
		$inlineContext->getContainer()->appendChild($img);

		return true;
	}
}
