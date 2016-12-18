<?php

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\OembedBlock;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;


class OembedRenderer implements BlockRendererInterface {
	/**
	 * @param OembedBlock $block
	 * @param ElementRendererInterface $htmlRenderer
	 * @param bool $inTightList
	 *
	 * @return HtmlElement|string
	 */
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false) {
		if (!($block instanceof OembedBlock)) {
			throw new \InvalidArgumentException('Incompatible block type: ' . get_class($block));
		}

		$attrs = [
			'class' => "rwd-media rwd-ratio-16-9"
		];

		return new HtmlElement('figure', $attrs, $block->getResponse()->getHtml());
	}
}
