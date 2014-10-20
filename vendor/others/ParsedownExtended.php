<?php
class ParsedownExtended extends ParsedownExtra {
	/** @var Nette\Application\UI\Presenter $presenter */
	public $presenter = null;

	function __construct() {
		$this->BlockTypes['!'][] = 'Details';
	}


	# Details
	protected function identifyDetails($Line) {
		if (preg_match('/^(['.$Line['text'][0].']{3,})[ ]*(.+)?[ ]*$/', $Line['text'], $matches)) {
			$summary = array(
				'name' => 'summary',
				'text' => 'Pro zobrazení zápletky klikni',
			);

			if (isset($matches[2])) {
				$summary['text'] = $matches[2];
			}
			
			$Block = [
			'char' => $Line['text'][0],
			'element' => [
				'name' => 'details',
				'handler' => 'elements',
				'text' => [$summary, ''],
				],
			];

			return $Block;
		}
	}


	protected function addToDetails($Line, $Block) {
		if (isset($Block['complete'])) {
			return;
		}
		if (isset($Block['interrupted'])) {
			$Block['element']['text'][1] .= "\n";
			unset($Block['interrupted']);
		}
		if (preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text'])) {
			$Block['element']['text'][1] = substr($Block['element']['text'][1], 1);
			$Block['complete'] = true;
			return $Block;
		}
		$Block['element']['text'][1] .= "\n".$Line['body'];

		return $Block;
	}


	protected function completeDetails($Block) {
		$Block['element']['text'][1] = $this->text($Block['element']['text'][1]);
		return $Block;
	}


	protected function identifyLink($excerpt) {
		$span = parent::identifyLink($excerpt);

		if (isset($span['element']['attributes']['href'])) {
			preg_match('/^(.+?):(.+)/', $span['element']['attributes']['href'], $m);
			if (isset($m[1])) {
				if ($m[1] === 'post') {
					$span['element']['attributes']['href'] = $this->presenter->link('post:show', $m[2]);
				} else if ($m[1] === 'page') {
					$span['element']['attributes']['href'] = $this->presenter->link('page:show', $m[2]);
				}
			}
		}

		return $span;
	}
}
