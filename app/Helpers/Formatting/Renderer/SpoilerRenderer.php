<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\Spoiler;
use InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Override;

class SpoilerRenderer implements NodeRendererInterface {
	/**
	 * @param Spoiler $node
	 */
	#[Override]
	public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement {
		if (!($node instanceof Spoiler)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $node::class);
		}

		$separator = $childRenderer->getBlockSeparator();
		$summary = new HtmlElement('summary', [], $node->getSummary() ?? 'Pro zobrazení zápletky klikni');
		$content = $summary . $separator . $childRenderer->renderNodes($node->children());

		return new HtmlElement('details', [], $content);
	}
}
