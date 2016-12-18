<?php

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\Spoiler;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;


class SpoilerRenderer implements BlockRendererInterface {
	/**
	 * @param Spoiler $block
	 * @param ElementRendererInterface $htmlRenderer
	 * @param bool $inTightList
	 *
	 * @return HtmlElement|string
	 */
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false) {
		if (!($block instanceof Spoiler)) {
			throw new \InvalidArgumentException('Incompatible block type: ' . get_class($block));
		}

		$summary = new HtmlElement('summary', [], $block->getSummary() ?: 'Pro zobrazení zápletky klikni');

		$attrs = [];
		foreach ($block->getData('attributes', []) as $key => $value) {
			$attrs[$key] = $htmlRenderer->escape($value, true);
		}
		return new HtmlElement('details', $attrs, $summary . "\n" . $htmlRenderer->renderBlocks($block->children()));
	}
}
