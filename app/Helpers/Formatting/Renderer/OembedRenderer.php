<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\OembedBlock;
use InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Override;

class OembedRenderer implements NodeRendererInterface {
	/**
	 * @param OembedBlock $node
	 */
	#[Override]
	public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement {
		if (!($node instanceof OembedBlock)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $node::class);
		}

		$attrs = [
			'class' => 'rwd-media rwd-ratio-16-9',
		];

		return new HtmlElement('figure', $attrs, $node->getEmbed()->html());
	}
}
