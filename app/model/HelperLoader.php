<?php

namespace App\Model;

use Nette;
use Nette\Utils\Html;

class HelperLoader extends Nette\Object {
	private $presenter;

	public function __construct($presenter) {
		$this->presenter = $presenter;
	}

	public function loader($args) {
		$args = func_get_args();
		$func = $args[0];
		unset($args[0]);

		if (method_exists($this, $func)) {
			return call_user_func_array(array($this, $func), $args);
		} else {
			return null;
		}
	}

	public function userLink($user) {
		return Html::el('a', $user->username)->href($this->presenter->link('profile:show', $user->id));
	}

	public function relDate($date) {
		if($date == (new \DateTime('today'))) {
			return '(dnes)';
		} else if($date == (new \DateTime('tomorrow'))) {
			return '(zítra)';
		}
		return '';
	}

	public function dateNA($time, $format = null) {
		if($time) {
			return \Latte\Runtime\Filters::date($time, $format);
		} else {
			return 'N/A';
		}
	}

	/** Truncate text with HTML tags
	* @param string $s string to be shortened, without comments and script blocks
	* @param int $limit number of returned characters
	* @return string shortened string with properly closed tags
	* @copyright Jakub Vrána, http://php.vrana.cz
	*/
	public function htmlTruncate($s, $limit) {
		static $empty_tags = array('area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param');
		$length = 0;
		$tags = array(); // dosud neuzavřené značky
		for($i=0; $i < strlen($s) && $length < $limit; $i++) {
			switch($s{$i}) {
			case '<':
				// načtení značky
				$start = $i+1;
				while($i < strlen($s) && $s{$i} != '>' && !ctype_space($s{$i})) {
					$i++;
				}
				$tag = strtolower(substr($s, $start, $i - $start));
				// přeskočení případných atributů
				$in_quote = '';
				while($i < strlen($s) && ($in_quote || $s{$i} != '>')) {
					if(($s{$i} === '"' || $s{$i} === "'") && !$in_quote) {
						$in_quote = $s{$i};
					} elseif($in_quote === $s{$i}) {
						$in_quote = '';
					}
					$i++;
				}
				if($s{$start} === '/') { // uzavírací značka
					$tags = array_slice($tags, array_search(substr($tag, 1), $tags) + 1);
				} elseif($s{$i-1} != '/' && !in_array($tag, $empty_tags)) { // otevírací značka
					array_unshift($tags, $tag);
				}
				break;
			case '&':
				$length++;
				while ($i < strlen($s) && $s{$i} != ';') {
					$i++;
				}
				break;
			default:
				$length++;
			}
		}
		$s = substr($s, 0, $i);
		if($tags) {
			$s .= "…</" . implode("></", $tags) . ">";
		}
		return $s;
	}
}
