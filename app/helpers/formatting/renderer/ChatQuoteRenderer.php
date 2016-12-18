<?php

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\ChatQuote;
use App\Model\HelperLoader;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;


class ChatQuoteRenderer implements BlockRendererInterface {
	/**
	 * @var ChatRepository
	 */
	private $chats;

	/**
	 * @var HelperLoader
	 */
	private $helperLoader;

	/**
	 * Constructor
	 *
	 * @param array $chats
	 * @param HelperLoader $helperLoader
	 */
	public function __construct($chats, HelperLoader $helperLoader) {
		$this->chats = $chats;
		$this->helperLoader = $helperLoader;
	}

	/**
	 * @param ChatQuote $block
	 * @param ElementRendererInterface $htmlRenderer
	 * @param bool $inTightList
	 *
	 * @return HtmlElement|string
	 */
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false) {
		if (!($block instanceof ChatQuote)) {
			throw new \InvalidArgumentException('Incompatible block type: ' . get_class($block));
		}

		$original = $this->chats->getById($block->getId());
		if (!$original) {
			return '';
		}

		$dom = new \Ogion\Utils\DarnDOMDocument;
		$dom->loadHTML($original->content);
		$xpath = new \DOMXPath($dom);
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
