<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\ChatQuote;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use DOMXPath;
use InvalidArgumentException;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use Ogion\Utils\DarnDOMDocument;

class ChatQuoteRenderer implements BlockRendererInterface {
	public function __construct(private ChatRepository $chats, private HelperLoader $helperLoader) {
	}

	/**
	 * @param ChatQuote $block
	 */
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false): HtmlElement|string {
		if (!($block instanceof ChatQuote)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $block::class);
		}

		$original = $this->chats->getById($block->getId());
		if (!$original) {
			return '';
		}

		$dom = new DarnDOMDocument();
		$dom->loadHTML($original->content);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query('//blockquote');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		$quotedText = $dom->saveHTML();

		$separator = $htmlRenderer->getOption('inner_separator', "\n");
		$userLink = $this->helperLoader->userLink($original->user, true);
		$userHeading = new HtmlElement('strong', [], $userLink);
		$content = $userHeading . $separator . trim($quotedText);
		$quote = new HtmlElement('blockquote', [], $content);

		return $quote;
	}
}
