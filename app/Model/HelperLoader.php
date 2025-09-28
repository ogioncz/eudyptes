<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Orm\User\User;
use DateTimeImmutable;
use Latte\Essential\Filters;
use Nette\Application\Application;
use Nette\Utils\Html;

class HelperLoader {
	public function __construct(private readonly Application $app) {
	}

	public function loader(string $filter): ?callable {
		if (method_exists($this, $filter)) {
			return [$this, $filter];
		}

		return null;
	}

	public function userLink(User $user, $visual = false): Html {
		return Html::el('a', $user->username)->href($this->app->getPresenter()->link('Profile:show', $user->id))->class('role-' . $user->role . ($visual ? ' role-visual' : ''));
	}

	public function relDate(DateTimeImmutable $date): string {
		if ($date == (new DateTimeImmutable('today'))) {
			return '(dnes)';
		} elseif ($date == (new DateTimeImmutable('tomorrow'))) {
			return '(zítra)';
		}

		return '';
	}

	public function dateNA(?DateTimeImmutable $time = null, ?string $format = null): ?string {
		if ($time) {
			return Filters::date($time, $format);
		} else {
			return 'N/A';
		}
	}

	/** Truncate text with HTML tags.
	 * @param string $text string to be shortened, without comments and script blocks
	 * @param int $limit number of returned characters
	 *
	 * @return string shortened string with properly closed tags
	 *
	 * @copyright Jakub Vrána, http://php.vrana.cz
	 */
	public function htmlTruncate(string $text, int $limit): string {
		static $empty_tags = ['area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param'];
		$length = 0;
		$textLength = \strlen($text);
		$tags = []; // not yet closed tags
		for ($i = 0; $i < $textLength && $length < $limit; ++$i) {
			switch ($text[$i]) {
				case '<':
					// load tag
					$start = $i + 1;
					while ($i < $textLength && $text[$i] !== '>' && !ctype_space($text[$i])) {
						++$i;
					}
					$tag = strtolower(substr($text, $start, $i - $start));
					// skip potential attributes
					$in_quote = '';
					while ($i < $textLength && ($in_quote || $text[$i] !== '>')) {
						if (($text[$i] === '"' || $text[$i] === "'") && !$in_quote) {
							$in_quote = $text[$i];
						} elseif ($in_quote === $text[$i]) {
							$in_quote = '';
						}
						++$i;
					}
					if ($text[$start] === '/') { // closing tag
						$tags = \array_slice($tags, array_search(substr($tag, 1), $tags, true) + 1);
					} elseif ($text[$i - 1] != '/' && !\in_array($tag, $empty_tags, true)) { // opening tag
						array_unshift($tags, $tag);
					}
					break;
				case '&':
					$length++;
					while ($i < $textLength && $text[$i] != ';') {
						++$i;
					}
					break;
				default:
					$length++;
			}
		}
		$text = substr($text, 0, $i);
		if ($tags) {
			$text .= '…</' . implode('></', $tags) . '>';
		}

		return $text;
	}
}
