<?php

namespace App\Model;

use Nette;
use Nette\Utils\Html;

class HelperLoader extends Nette\Object {
	private $presenter;

	public function __construct(Nette\Application\UI\Presenter $presenter) {
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

	public function userLink(User $user) {
		return Html::el('a', $user->username)->href($this->presenter->link('profile:show', $user->id));
	}

	public function relDate(\DateTime $date) {
		if ($date == (new \DateTime('today'))) {
			return '(dnes)';
		} else if ($date == (new \DateTime('tomorrow'))) {
			return '(zítra)';
		}
		return '';
	}

	public function dateNA(\DateTime $time = null, $format = null) {
		if ($time) {
			return \Latte\Runtime\Filters::date($time, $format);
		} else {
			return 'N/A';
		}
	}

	/** Truncate text with HTML tags
	* @param string $text string to be shortened, without comments and script blocks
	* @param int $limit number of returned characters
	* @return string shortened string with properly closed tags
	* @copyright Jakub Vrána, http://php.vrana.cz
	*/
	public function htmlTruncate($text, $limit) {
		static $empty_tags = array('area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param');
		$length = 0;
		$textLength = strLen($text);
		$tags = array(); // not yet closed tags
		for ($i = 0; $i < $textLength && $length < $limit; $i++) {
			switch ($text{$i}) {
			case '<':
				// load tag
				$start = $i + 1;
				while ($i < $textLength && $text{$i} !== '>' && !ctype_space($text{$i})) {
					$i++;
				}
				$tag = strToLower(subStr($text, $start, $i - $start));
				// skip potential attributes
				$in_quote = '';
				while ($i < $textLength && ($in_quote || $text{$i} !== '>')) {
					if (($text{$i} === '"' || $text{$i} === "'") && !$in_quote) {
						$in_quote = $text{$i};
					} else if ($in_quote === $text{$i}) {
						$in_quote = '';
					}
					$i++;
				}
				if ($text{$start} === '/') { // closing tag
					$tags = array_slice($tags, array_search(subStr($tag, 1), $tags) + 1);
				} else if ($text{$i - 1} != '/' && !in_array($tag, $empty_tags)) { // opening tag
					array_unshift($tags, $tag);
				}
				break;
			case '&':
				$length++;
				while ($i < $textLength && $text{$i} != ';') {
					$i++;
				}
				break;
			default:
				$length++;
			}
		}
		$text = subStr($text, 0, $i);
		if ($tags) {
			$text .= '…</' . implode('></', $tags) . '>';
		}
		return $text;
	}
}
