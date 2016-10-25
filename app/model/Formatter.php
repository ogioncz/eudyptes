<?php

namespace App\Model;

use Nette;
use Alb\OEmbed;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class Formatter extends Nette\Object {
	public static $OEMBED_WHITELIST = ['www.youtube.com', 'youtu.be', 'vimeo.com', 'soundcloud.com', 'twitter.com'];

	/** @var PageRepository */
	public $pages;

	/** @var \Parsedown */
	public $parsedown;

	/** @var \HTMLPurifier */
	public $purifier;

	/** @var OEmbed\Simple */
	public $oembed;

	public function __construct(PageRepository $pages, \Parsedown $parsedown, \HTMLPurifier $purifier, OEmbed\Simple $oembed) {
		$this->pages = $pages;
		$this->parsedown = $parsedown;
		$this->purifier = $purifier;
		$this->oembed = $oembed;
	}

	public function format($text) {
		$text = $this->replaceEmoticons($text);

		$text = $this->replaceOembed($text);

		$text = $this->replaceProps($text);

		$text = $this->replaceWikiLinks($text);

		$text = $this->replaceCustomTags($text);

		$text = $this->parsedown->text($text);

		$text = $this->purifier->purify($text);

		return ['text' => $text, 'errors' => $this->purifier->context->get('ErrorCollector')->getRaw()];
	}

	public function replaceOembed($text) {
		$replacements = [];
		$alpha = "a-z\x80-\xFF";
		$domain = "[0-9$alpha](?:[-0-9$alpha]{0,61}[0-9$alpha])?";
		$topDomain = "[$alpha][-0-9$alpha]{0,17}[$alpha]";
		$text = preg_replace_callback("(^https?://((?:$domain\\.)*$topDomain|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?$)im", function($match) {
			if (!isset($replacements[$match[0]])) {
				if (in_array($match[1], Formatter::$OEMBED_WHITELIST)) {
					try {
						$request = $this->oembed->request($match[0]);
						if ($request) {
							return $replacements[$match[0]] = '<figure class="rwd-media rwd-ratio-16-9">' . $request->getHtml() . '</figure>';
						}
					} catch (\Exception $e) {
						\Tracy\Debugger::log($e);
					} // can’t serve, link is better than nothing so let’s leave it at that
				}
				return $match[0];
			}
			return $replacements[$match[0]];
		}, $text);

		return $text;
	}

	public function replaceProps($text) {
		$text = preg_replace('/<prop>vystavba<\/prop>/i', '<figure><img src="http://cdn.fan-club-penguin.cz/img/vystavba.gif"></figure>', $text);
		$text = preg_replace('/<prop>fieldop<\/prop>/i', '<figure><img alt="Field-op" src="http://upload.fan-club-penguin.cz/files/system/phone-red-pulsing-big.gif"></figure>', $text);
		$text = preg_replace('/<prop>message<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-blue-pulsing-big.gif"></figure>', $text);
		$text = preg_replace('/<prop>message2013<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-2013.png" width="146" height="200"></figure>', $text);
		$text = preg_replace('/<prop>sponsored<\/prop>/i', '<img title="Sponsorovaná párty" alt="Sponsorovaná párty" src="http://cdn.fan-club-penguin.cz/img/sponsored.png" width="14" height="14">', $text);
		$text = preg_replace('/<prop>multiclip<\/prop>/i', '<span class="icon-random" title="Více klipů náhodně míchaných při přehrávání"></span>', $text);

		return $text;
	}

	public function replaceCustomTags($text) {
		$text = CustomTags::age($text);
		$text = CustomTags::item($text);
		$text = CustomTags::coins($text);
		$text = CustomTags::music($text);

		return $text;
	}

	public function replaceWikiLinks($text) {
		$text = preg_replace_callback('~\[\[([^\]|\n]+)(?:\|([^\]|\n]+))?\]\]~u', function($matches) {
			$link = $label = $matches[1];
			if (count($matches) === 3) {
				$label = $matches[2];
			}

			$link = Strings::webalize($link, '/');

			$redlink = $this->pages->findBy(['slug' => $link])->countStored() === 0;

			return '<a href="page:' . $link . '"' . ($redlink ? ' class="redlink"' : '') . '>' . $label . '</a>';
		}, $text);

		return $text;
	}

	public function replaceEmoticons($text) {
		$text = preg_replace('/(😕|:-\/)/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/meh.svg" alt="😕" width="30" height="29">', $text);
		$text = preg_replace('/(😠|>:-?\(|&gt;:-?\()/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/angry.svg" alt="😠" width="30" height="29">', $text);
		$text = preg_replace('/(🎂|\(cake\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/cake.svg" alt="🎂" width="30" height="29">', $text);
		$text = preg_replace('/(☕|\(coffee\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/coffee.svg" alt="☕" width="30" height="29">', $text);
		$text = preg_replace('/(⚘|\(flower\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/flower.svg" alt="⚘" width="30" height="29">', $text);
		$text = preg_replace('/(🙁|\(frown(?:ing)?\)|:\'-?\()/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/frowning.svg" alt="🙁" width="30" height="29">', $text);
		$text = preg_replace('/(☺|:-?\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg" alt="☺" width="30" height="29">', $text);
		$text = preg_replace('/(♥|<3|&lt;3|\(love\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/heart.svg" alt="♥" width="30" height="29">', $text);
		$text = preg_replace('/(🕹|\(joystick\)|\(game\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/joystick.svg" alt="🕹" width="30" height="29">', $text);
		$text = preg_replace('/(😃|:-?D)/', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/laughing.svg" alt="😃" width="30" height="29">', $text);
		$text = preg_replace('/(💡|\(bulb\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/light-bulb.svg" alt="💡" width="30" height="29">', $text);
		$text = preg_replace('/(🌃|\(night\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/moon-and-stars.svg" alt="🌃" width="30" height="29">', $text);
		$text = preg_replace('/(🍕|\(pizza\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/pizza.svg" alt="🍕" width="30" height="29">', $text);
		$text = preg_replace('/(😹|\(joy\)|:\'-?D)/', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/rabbit-face-with-tears-of-joy.svg" alt="😹" width="30" height="29">', $text);
		$text = preg_replace('/(😻|\(cat\))/', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/smiling-cat-face-with-heart-shaped-eyes.svg" alt="😻" width="30" height="29">', $text);
		$text = preg_replace('/(☹|:-?\()/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sad.svg" alt="☹" width="30" height="29">', $text);
		$text = preg_replace('/(☘|\(clover\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/shamrock.svg" alt="☘" width="30" height="29">', $text);
		$text = preg_replace('/(😝|:-?p)/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sticking-out-tongue.svg" alt="😝" width="30" height="29">', $text);
		$text = preg_replace('/(😐|:-?\|)/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/straight.svg" alt="😐" width="30" height="29">', $text);
		$text = preg_replace('/(🌣|☀|☼|\(sun\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sun.svg" alt="🌣" width="30" height="29">', $text);
		$text = preg_replace('/(😮|:-?o)/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/surprised.svg" alt="😮" width="30" height="29">', $text);
		$text = preg_replace('/(🎵|\(toot\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/toot.svg" alt="🎵" width="30" height="29">', $text);
		$text = preg_replace('/(😉|;-?\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/winking.svg" alt="😉" width="30" height="29">', $text);
		$text = preg_replace('/(\*@\*|\(puffle\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/puffle.svg" alt="*@*" width="30" height="29">', $text);
		$text = preg_replace('/(\(coin\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/coin.svg" alt="\1" width="30" height="29">', $text);
		$text = preg_replace('/(\(icebrown\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/chocolate-ice-cream.svg" alt="\1" width="30" height="29">', $text);
		$text = preg_replace('/(\(epf\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/operation-puffle-epf-logo.svg" alt="\1" width="30" height="29">', $text);
		$text = preg_replace('/(\(icepink\))/i', '<img src="https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/strawberry-ice-cream.svg" alt="\1" width="30" height="29">', $text);

		return $text;
	}

	public function urlsToLinks($text) {
		$re = '/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui';
		$offset = 0;
		while (strpos($text, '://', $offset) && preg_match($re, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
			$url = $matches[0][0];
			$urlLength = strlen($url);
			$urlPosition = $matches[0][1];
			$markup = '<a href="'.$url.'">'.$url.'</a>';
			$markupLength = strlen($markup);
			$text = substr_replace($text, $markup, $urlPosition, $urlLength);
			$offset = $urlPosition + $markupLength;
		}

		return $text;
	}

	public function formatErrors($errors) {
		$list = Html::el('ul');
		foreach ($errors as $error) {
			$list->addHtml(Html::el('li', 'Na řádku ' . $error[0] . ': ' . $error[2]));
		}
		return $list;
	}
}
