<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\OembedBlock;
use InvalidArgumentException;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;

class OembedRenderer implements BlockRendererInterface {
	/**
	 * @param OembedBlock $block
	 * @param bool $inTightList
	 */
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false): HtmlElement {
		if (!($block instanceof OembedBlock)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $block::class);
		}

		$attrs = [
			'class' => 'rwd-media rwd-ratio-16-9',
		];

		return new HtmlElement('figure', $attrs, $block->getResponse()->getHtml());
	}
}
