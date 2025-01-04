<?php

declare(strict_types=1);

namespace App\Helpers\Formatting\Renderer;

use App\Helpers\Formatting\Element\Spoiler;
use InvalidArgumentException;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use Override;

class SpoilerRenderer implements BlockRendererInterface {
	/**
	 * @param Spoiler $block
	 * @param bool $inTightList
	 */
	#[Override]
	public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false): HtmlElement {
		if (!($block instanceof Spoiler)) {
			throw new InvalidArgumentException('Incompatible block type: ' . $block::class);
		}

		$separator = $htmlRenderer->getOption('inner_separator', "\n");
		$summary = new HtmlElement('summary', [], $block->getSummary() ?: 'Pro zobrazení zápletky klikni');
		$content = $summary . $separator . $htmlRenderer->renderBlocks($block->children());

		return new HtmlElement('details', [], $content);
	}
}
