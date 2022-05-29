<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Extension;

use App\Helpers\Formatting\Formatter;
use App\Helpers\Formatting\Parser\ChatQuoteParser;
use App\Helpers\Formatting\Parser\EmoticonParser;
use App\Helpers\Formatting\Parser\UrlParser;
use App\Helpers\Formatting\Renderer\ChatQuoteRenderer;
use App\Model\ChatRepository;
use App\Model\HelperLoader;
use League\CommonMark\Block\Renderer as BlockRenderer;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Inline\Renderer as InlineRenderer;

class CommonMarkChatExtension implements ExtensionInterface {
	public function __construct(
		private ChatRepository $chats,
		private HelperLoader $helperLoader,
	) {
	}

	public function register(ConfigurableEnvironmentInterface $environment): void {
		$environment->addBlockParser(new ChatQuoteParser(), 100);

		$environment->addInlineParser(new EmoticonParser(Formatter::$images, Formatter::$emoticons), 100);
		$environment->addInlineParser(new UrlParser(), 55);

		$environment->addBlockRenderer(\League\CommonMark\Block\Element\Document::class, new BlockRenderer\DocumentRenderer(), 0);
		$environment->addBlockRenderer(\League\CommonMark\Block\Element\Paragraph::class, new BlockRenderer\ParagraphRenderer(), 0);

		$environment->addBlockRenderer(\App\Helpers\Formatting\Element\ChatQuote::class, new ChatQuoteRenderer($this->chats, $this->helperLoader), 0);
		$environment->addInlineRenderer(\League\CommonMark\Inline\Element\Image::class, new InlineRenderer\ImageRenderer(), 0);
		$environment->addInlineRenderer(\League\CommonMark\Inline\Element\Link::class, new InlineRenderer\LinkRenderer(), 0);
		$environment->addInlineRenderer(\League\CommonMark\Inline\Element\Text::class, new InlineRenderer\TextRenderer(), 0);
	}
}
