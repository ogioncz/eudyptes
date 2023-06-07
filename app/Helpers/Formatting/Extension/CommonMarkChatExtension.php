<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Extension;

use App\Helpers\Formatting\Element\ChatQuote;
use App\Helpers\Formatting\Formatter;
use App\Helpers\Formatting\Parser\ChatQuoteParser;
use App\Helpers\Formatting\Parser\EmoticonParser;
use App\Helpers\Formatting\Parser\UrlParser;
use App\Helpers\Formatting\Renderer\ChatQuoteRenderer;
use App\Model\ChatRepository;
use App\Model\HelperLoader;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\Block\Renderer as BlockRenderer;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Element\Text;
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

		$environment->addBlockRenderer(Document::class, new BlockRenderer\DocumentRenderer(), 0);
		$environment->addBlockRenderer(Paragraph::class, new BlockRenderer\ParagraphRenderer(), 0);

		$environment->addBlockRenderer(ChatQuote::class, new ChatQuoteRenderer($this->chats, $this->helperLoader), 0);
		$environment->addInlineRenderer(Image::class, new InlineRenderer\ImageRenderer(), 0);
		$environment->addInlineRenderer(Link::class, new InlineRenderer\LinkRenderer(), 0);
		$environment->addInlineRenderer(Text::class, new InlineRenderer\TextRenderer(), 0);
	}
}
