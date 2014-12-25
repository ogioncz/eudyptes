<?php

namespace App\Model;

use Nette;
use Alb\OEmbed;
use Nette\Utils\Html;

class Formatter extends Nette\Object {
	public static $OEMBED_WHITELIST = ['www.youtube.com', 'youtu.be', 'vimeo.com'];

	/** @var \Parsedown */
	public $parsedown;

	/** @var \HTMLPurifier */
	public $purifier;

	/** @var OEmbed\Simple */
	public $oembed;

	private $replacements = [];

	public function __construct(\Parsedown $parsedown, \HTMLPurifier $purifier, OEmbed\Simple $oembed) {
		$this->parsedown = $parsedown;
		$this->purifier = $purifier;
		$this->oembed = $oembed;
	}

	public function format($text) {
		$alpha = "a-z\x80-\xFF";
		$domain = "[0-9$alpha](?:[-0-9$alpha]{0,61}[0-9$alpha])?";
		$topDomain = "[$alpha][-0-9$alpha]{0,17}[$alpha]";
		if (preg_match_all("(^https?://((?:$domain\\.)*$topDomain|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|\\[[0-9a-f:]{3,39}\\])(:\\d{1,5})?(/\\S*)?$)im", $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				if (in_array($match[1], Formatter::$OEMBED_WHITELIST) && !isset($this->replacements[$match[0]])) {
					try {
						$request = $this->oembed->request($match[0]);
						if ($request) {
							$this->replacements[$match[0]] = '<figure class="rwd-media rwd-ratio-16-9">' . $request->getHtml() . '</figure>';
						}
					} catch (\Exception $e) {
						\Tracy\Debugger::log($e);
					} // can’t serve, link is better than nothing so let’s leave it at that
				}
			}
		}
		$text = strTr($text, $this->replacements);

		$text = preg_replace('/<prop>vystavba<\/prop>/i', '<figure><img src="http://cdn.fan-club-penguin.cz/img/vystavba.gif"></figure>', $text);
		$text = preg_replace('/<prop>fieldop<\/prop>/i', '<figure><img alt="Field-op" src="http://upload.fan-club-penguin.cz/files/system/phone-red-pulsing-big.gif"></figure>', $text);
		$text = preg_replace('/<prop>message<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-blue-pulsing-big.gif"></figure>', $text);
		$text = preg_replace('/<prop>message2013<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-2013.png" width="146" height="200"></figure>', $text);
		$text = preg_replace('/<prop>sponsored<\/prop>/i', '<img title="Sponsorovaná párty" alt="Sponsorovaná párty" src="http://cdn.fan-club-penguin.cz/img/sponsored.png" width="14" height="14">', $text);
		$text = preg_replace('/<prop>multiclip<\/prop>/i', '<span class="icon-random" title="Více klipů náhodně míchaných při přehrávání"></span>', $text);

		$text = CustomTags::age($text);
		$text = CustomTags::item($text);
		$text = CustomTags::coins($text);

		$text = $this->parsedown->text($text);

		$text = $this->purifier->purify($text);

		return ['text' => $text, 'errors' => $this->purifier->context->get('ErrorCollector')->getRaw()];
	}

	public function formatErrors($errors) {
		$list = Html::el('ul');
		foreach ($errors as $error) {
			$list->add(Html::el('li', 'Na řádku ' . $error[0] . ': ' . $error[2]));
		}
		return $list;
	}
}
