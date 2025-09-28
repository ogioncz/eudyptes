<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Extension;

use App\Helpers\Formatting\Element\ChatQuote;
use App\Helpers\Formatting\Formatter;
use App\Helpers\Formatting\Parser\ChatQuoteParser;
use App\Helpers\Formatting\Parser\EmoticonParser;
use App\Helpers\Formatting\Parser\UrlParser;
use App\Helpers\Formatting\Renderer\ChatQuoteRenderer;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\LinkRenderer;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Renderer\Block\DocumentRenderer;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\CommonMark\Renderer\Inline\TextRenderer;
use Override;

class CommonMarkChatExtension implements ExtensionInterface {
	public function __construct(
		private readonly ChatRepository $chats,
		private readonly HelperLoader $helperLoader,
	) {
	}

	#[Override]
	public function register(EnvironmentBuilderInterface $environment): void {
		$environment->addBlockStartParser(new ChatQuoteParser(), 100);

		$environment->addInlineParser(new EmoticonParser(Formatter::IMAGES, Formatter::EMOTICONS), 100);
		$environment->addInlineParser(new UrlParser(), 55);

		$environment->addRenderer(Document::class, new DocumentRenderer());
		$environment->addRenderer(Paragraph::class, new ParagraphRenderer());

		$environment->addRenderer(ChatQuote::class, new ChatQuoteRenderer($this->chats, $this->helperLoader));
		$environment->addRenderer(Image::class, new ImageRenderer());
		$environment->addRenderer(Link::class, new LinkRenderer());
		$environment->addRenderer(Text::class, new TextRenderer());
	}
}
