<?php

namespace App\Helpers\Formatting\Parser;

use App\Helpers\Formatting\Element\Spoiler;
use League\CommonMark\Block\Parser\AbstractBlockParser;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;

class SpoilerParser extends AbstractBlockParser {
	/**
	 * @param ContextInterface $context
	 * @param Cursor $cursor
	 *
	 * @return bool
	 */
	public function parse(ContextInterface $context, Cursor $cursor) {
		if ($cursor->isIndented()) {
			return false;
		}

		$previousState = $cursor->saveState();
		$spoiler = $cursor->match('(^¡¡¡(\s*.+)?)');
		if (!is_null($spoiler)) {
			$summary = trim(mb_substr($spoiler, mb_strlen('¡¡¡')));
			if ($summary !== '') {
				$context->addBlock(new Spoiler($summary));
			} else {
				$context->addBlock(new Spoiler());
			}
			return true;
		} else {
			$cursor->restoreState($previousState);
			if (!is_null($cursor->match('/^!!!$/'))) {
				$container = $context->getContainer();
				do {
					if ($container instanceof Spoiler) {
						$context->setContainer($container);
						$container->finalize($context, $context->getLineNumber());
						$context->getBlockCloser()->setLastMatchedContainer($container);
						return true;
					}
				} while ($container = $container->parent());
			}
		}

		$cursor->restoreState($previousState);
		return false;
	}
}
