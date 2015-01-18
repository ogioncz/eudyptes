<?php
class ParsedownExtended extends ParsedownExtra {
	function __construct() {
		$this->BlockTypes[chr(ord('¡'))][] = 'Details';
		parent::__construct();
	}


	// Details
	protected function blockDetails($Line) {
		if (preg_match('/^([¡]{3,})[ ]*(.+)?[ ]*$/', $Line['text'], $matches)) {
			$Block = [
				'char' => $Line['text'][0],
				'markup' => ''
			];

			$Block['summary'] = 'Pro zobrazení zápletky klikni';

			if (isset($matches[2])) {
				$Block['summary'] = $matches[2];
			}

			return $Block;
		}
	}


	protected function blockDetailsContinue($Line, $Block) {
		if (isset($Block['complete'])) {
			return;
		}
		if (isset($Block['interrupted'])) {
			$Block['markup'] .= "\n";
			unset($Block['interrupted']);
		}
		if (preg_match('/^!{3,}[ ]*$/', $Line['text'])) {
			$Block['markup'] = substr($Block['markup'], 1);
			$Block['complete'] = true;
			return $Block;
		}
		$Block['markup'] .= "\n".$Line['body'];

		return $Block;
	}


	protected function blockDetailsComplete($Block) {
		$Block['markup'] = '<details>' . PHP_EOL
		. '<summary>' . $Block['summary'] . '</summary>' . PHP_EOL
		. $this->text($Block['markup']) . PHP_EOL
		. '</details>';
		return $Block;
	}

	public function urlsToLinks($text) {
		return $this->unmarkedInlineUrl($text);
	}
}
