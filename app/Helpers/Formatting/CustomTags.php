<?php

declare(strict_types=1);

namespace App\Helpers\Formatting;

use DateTimeImmutable;
use Nette\SmartObject;
use Nette\Utils\Json;

class CustomTags {
	use SmartObject;

	public static function item($text): array|string|null {
		$text = preg_replace_callback('/<(?P<prefix>cp-)(?P<type>(?:item|furniture|igloo|floor|location|puffleitem))(?P<attr>[^>]*)>(?P<id>\d+)<\/(?P=prefix)(?P=type)>/sU', function(array $match): string {
			if ($match['type'] === 'item') {
				$match['type'] = 'paper';
			} elseif ($match['type'] === 'furniture') {
				$match['type'] = 'furniture';
			} elseif ($match['type'] === 'puffleitem') {
				$match['type'] = 'puffles';
			} elseif ($match['type'] === 'igloo') {
				$match['type'] = 'igloos/buildings';
			} elseif ($match['type'] === 'floor') {
				$match['type'] = 'igloos/flooring';
			} elseif ($match['type'] === 'location') {
				$match['type'] = 'igloos/locations';
			}
			$match['attr'] = Json::decode('{' . preg_replace('/([\w-]+)=("[^"]+")/', '"\1": \2, ', (string) $match['attr']) . '"data-final": "true"}');

			if (isset($match['attr']->size)) {
				$match['size'] = (int) $match['attr']->size;
				unset($match['attr']->size);
			} else {
				$match['size'] = 60;
			}

			unset($match['attr']->{'data-final'});
			$match['attr']->class = isset($match['attr']->class) ? $match['attr']->class . ' cpitem' : 'cpitem';
			$match['attr'] = Json::encode($match['attr']);
			$match['attr'] = preg_replace('/"([^"]+)":("[^"]+"),?/', '\1=\2 ', $match['attr']);
			$match['attr'] = trim((string) $match['attr'], '{} ');

			return '<img src="http://mediacache.fan-club-penguin.cz/game/items/images/' . $match['type'] . '/icon/' . $match['size'] . '/' . $match['id'] . '.png" alt="" width="' . $match['size'] . '" height="' . $match['size'] . '" ' . $match['attr'] . '>';
		}, (string) $text);

		return $text;
	}

	public static function coins($text): string|array|null {
		$text = preg_replace('/<cp-coins>(\d+)<\/cp-coins>/sU', '<span class="cpcoins cpitem"><span>\1<span class="sr-only"> mincí</span></span></span>', (string) $text);

		return $text;
	}

	public static function age($text): array|string|null {
		$text = preg_replace_callback('/<cp-age>(\d{4}-\d{2}-\d{2})<\/cp-age>/sU', function($match): string {
			$today = new DateTimeImmutable();
			$registration = new DateTimeImmutable($match[1]);

			return $today->diff($registration)->format('%a');
		}, (string) $text);

		return $text;
	}

	public static function music($text): ?string {
		return preg_replace('/<cp-music>(\d+)<\/cp-music>/sU', '<audio src="http://upload.fan-club-penguin.cz/hudba/$1.mp3" loop="loop" autoplay="autoplay" type="audio/mp3"></audio>', (string) $text);
	}
}
