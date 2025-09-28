<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\ChatQuote;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use DOMXPath;
use InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Ogion\Utils\DarnDOMDocument;
use Override;

class ChatQuoteRenderer implements NodeRendererInterface {
	public function __construct(private readonly ChatRepository $chats, private readonly HelperLoader $helperLoader) {
	}

	/**
	 * @param ChatQuote $node
	 */
	#[Override]
	public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement|string {
		if (!($node instanceof ChatQuote)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $node::class);
		}

		$original = $this->chats->getById($node->getId());
		if (!$original) {
			return '';
		}

		$dom = new DarnDOMDocument();
		$dom->loadHTML($original->content);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query('//blockquote');
		foreach ($nodes as $domNode) {
			$domNode->parentNode->removeChild($domNode);
		}
		$quotedText = $dom->saveHTML();

		$separator = $childRenderer->getBlockSeparator();
		$userLink = $this->helperLoader->userLink($original->user, true);
		$userHeading = new HtmlElement('strong', [], $userLink);
		$content = $userHeading . $separator . trim($quotedText);
		$quote = new HtmlElement('blockquote', [], $content);

		return $quote;
	}
}
