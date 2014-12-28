<?php

namespace App\Model;

use Nette;
use Nette\Utils\Json;
use Nette\Utils\DateTime;

class CustomTags extends Nette\Object {
	public static function item($text) {
		$text = preg_replace_callback('/<(?P<prefix>cp-)(?P<type>(?:item|furniture|igloo|floor|location|puffleitem))(?P<attr>[^>]*)>(?P<id>\d+)<\/(?P=prefix)(?P=type)>/sU', function($match) {
			if($match['type'] === 'item') {
				$match['type'] = 'paper';
			} else if($match['type'] === 'furniture') {
				$match['type'] = 'furniture';
			} else if($match['type'] === 'puffleitem') {
				$match['type'] = 'puffles';
			} else if($match['type'] === 'igloo') {
				$match['type'] = 'igloos/buildings';
			} else if($match['type'] === 'floor') {
				$match['type'] = 'igloos/flooring';
			} else if($match['type'] === 'location') {
				$match['type'] = 'igloos/locations';
			}
			$match['attr'] = Json::decode('{' . preg_replace('/([\w-]+)=("[^"]+")/', '"\1": \2, ', $match['attr']) . '"data-final": "true"}');

			if(isset($match['attr']->size)) {
				$match['size'] = (int) $match['attr']->size;
				unset($match['attr']->size);
			} else {
				$match['size'] = 60;
			}

			unset($match['attr']->{'data-final'});
			$match['attr']->class = isset($match['attr']->class) ? $match['attr']->class . ' cpitem' : 'cpitem';
			$match['attr'] = Json::encode($match['attr']);
			$match['attr'] = preg_replace('/"([^"]+)":("[^"]+"),?/', '\1=\2 ', $match['attr']);
			$match['attr'] = trim($match['attr'], '{} ');

			return '<img src="http://media8.clubpenguin.com/game/items/images/' . $match['type'] . '/icon/' . $match['size'] . '/' . $match['id'] . '.png" alt="" width="' . $match['size'] . '" height="' . $match['size'] . '" ' . $match['attr'] . '>';
		}, $text);
		return $text;
	}

	public static function coins($text) {
		$text = preg_replace('/<cp-coins>(\d+)<\/cp-coins>/sU', '<span class="cpcoins"><img src="http://upload.fan-club-penguin.cz/system/coins.png" alt="" width="60" height="60" class="cpitem"><span>\1<span class="sr-only"> mincí</span></span></span>', $text);
		return $text;
	}

	public static function age($text) {
		$text = preg_replace_callback('/<cp-age>(\d{4}-\d{2}-\d{2})<\/cp-age>/sU', function($match) {
			$today = new DateTime();
			$registration = new DateTime($match[1]);
			return $today->diff($registration)->format('%a');
		}, $text);
		return $text;
	}
}