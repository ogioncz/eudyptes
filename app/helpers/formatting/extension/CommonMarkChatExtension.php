<?php

namespace App\Helpers\Formatting\Extension;

use App\Helpers\Formatting\Formatter;
use App\Helpers\Formatting\Parser\EmoticonParser;
use App\Helpers\Formatting\Parser\UrlParser;
use App\Helpers\Formatting\Parser\ChatQuoteParser;
use App\Helpers\Formatting\Renderer\ChatQuoteRenderer;
use App\Model\ChatRepository;
use App\Model\HelperLoader;
use League\CommonMark\Block\Parser as BlockParser;
use League\CommonMark\Extension\Extension;
use League\CommonMark\Block\Renderer as BlockRenderer;
use League\CommonMark\Inline\Parser as InlineParser;
use League\CommonMark\Inline\Processor as InlineProcessor;
use League\CommonMark\Inline\Renderer as InlineRenderer;

class CommonMarkChatExtension extends Extension {
	/** @var ChatRepository */
	private $chats;

	/** @var HelperLoader */
	private $helperLoader;

	/**
	 * Constructor
	 *
	 * @param ChatRepository $chats
	 * @param HelperLoader $helperLoader
	 */
	public function __construct(ChatRepository $chats, HelperLoader $helperLoader) {
		$this->chats = $chats;
		$this->helperLoader = $helperLoader;
	}

	/**
	 * @return BlockParser\BlockParserInterface[]
	 */
	public function getBlockParsers() {
		return [
			// This order is important
			new ChatQuoteParser(),
		];
	}

	/**
	 * @return InlineParser\InlineParserInterface[]
	 */
	public function getInlineParsers() {
		return [
			new EmoticonParser(Formatter::$images, Formatter::$emoticons),
			new UrlParser(),
		];
	}

	/**
	 * @return InlineProcessor\InlineProcessorInterface[]
	 */
	public function getInlineProcessors() {
		return [];
	}

	/**
	 * @return BlockRenderer\BlockRendererInterface[]
	 */
	public function getBlockRenderers() {
		return [
			'League\CommonMark\Block\Element\Document' => new BlockRenderer\DocumentRenderer(),
			'League\CommonMark\Block\Element\Paragraph' => new BlockRenderer\ParagraphRenderer(),
			'App\Helpers\Formatting\Element\ChatQuote' => new ChatQuoteRenderer($this->chats, $this->helperLoader),
		];
	}

	/**
	 * @return InlineRenderer\InlineRendererInterface[]
	 */
	public function getInlineRenderers() {
		return [
			'League\CommonMark\Inline\Element\Image' => new InlineRenderer\ImageRenderer(),
			'League\CommonMark\Inline\Element\Link' => new InlineRenderer\LinkRenderer(),
			'League\CommonMark\Inline\Element\Text' => new InlineRenderer\TextRenderer(),
		];
	}
}
