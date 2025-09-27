<?php

declare(strict_types=1);

namespace App\Helpers\Formatting;

use App\Helpers\Formatting\Element\OembedBlock;
use App\Helpers\Formatting\Element\Spoiler;
use App\Helpers\Formatting\Parser\EmoticonParser;
use App\Helpers\Formatting\Parser\OembedParser;
use App\Helpers\Formatting\Parser\SpoilerParser;
use App\Helpers\Formatting\Renderer\OembedRenderer;
use App\Helpers\Formatting\Renderer\SpoilerRenderer;
use App\Model\Orm\Page\PageRepository;
use Cohensive\OEmbed\Factory as OEmbedFactory;
use HTMLPurifier;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class Formatter {
	/** @var array<string> */
	public static $OEMBED_WHITELIST = ['www.youtube.com', 'youtu.be', 'vimeo.com', 'soundcloud.com', 'twitter.com'];

	/** @var array<string, array{src: string, alt: string, width: int, height: int}> */
	public static $images = [
		'meh' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/meh.svg', 'alt' => '😕', 'width' => 30, 'height' => 29],
		'angry' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/angry.svg', 'alt' => '😠', 'width' => 30, 'height' => 29],
		'cake' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/cake.svg', 'alt' => '🎂', 'width' => 30, 'height' => 29],
		'coffee' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/coffee.svg', 'alt' => '☕', 'width' => 30, 'height' => 29],
		'flower' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/flower.svg', 'alt' => '⚘', 'width' => 30, 'height' => 29],
		'frowning' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/frowning.svg', 'alt' => '🙁', 'width' => 30, 'height' => 29],
		'happy' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg', 'alt' => '☺', 'width' => 30, 'height' => 29],
		'heart' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/heart.svg', 'alt' => '♥', 'width' => 30, 'height' => 29],
		'joystick' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/joystick.svg', 'alt' => '🕹', 'width' => 30, 'height' => 29],
		'laughing' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/laughing.svg', 'alt' => '😃', 'width' => 30, 'height' => 29],
		'light-bulb' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/light-bulb.svg', 'alt' => '💡', 'width' => 30, 'height' => 29],
		'moon-and-stars' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/moon-and-stars.svg', 'alt' => '🌃', 'width' => 30, 'height' => 29],
		'pizza' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/pizza.svg', 'alt' => '🍕', 'width' => 30, 'height' => 29],
		'rabbit-face-with-tears-of-joy' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/rabbit-face-with-tears-of-joy.svg', 'alt' => '😹', 'width' => 30, 'height' => 29],
		'smiling-cat-face-with-heart-shaped-eyes' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/smiling-cat-face-with-heart-shaped-eyes.svg', 'alt' => '😻', 'width' => 30, 'height' => 29],
		'sad' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sad.svg', 'alt' => '☹', 'width' => 30, 'height' => 29],
		'shamrock' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/shamrock.svg', 'alt' => '☘', 'width' => 30, 'height' => 29],
		'sticking-out-tongue' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sticking-out-tongue.svg', 'alt' => '😝', 'width' => 30, 'height' => 29],
		'straight' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/straight.svg', 'alt' => '😐', 'width' => 30, 'height' => 29],
		'sun' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/sun.svg', 'alt' => '🌣', 'width' => 30, 'height' => 29],
		'surprised' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/surprised.svg', 'alt' => '😮', 'width' => 30, 'height' => 29],
		'toot' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/toot.svg', 'alt' => '🎵', 'width' => 30, 'height' => 29],
		'winking' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/winking.svg', 'alt' => '😉', 'width' => 30, 'height' => 29],
		'puffle' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/puffle.svg', 'alt' => '*@*', 'width' => 30, 'height' => 29],
		'coin' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/coin.svg', 'alt' => '(coin)', 'width' => 30, 'height' => 29],
		'chocolate-ice-cream' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/chocolate-ice-cream.svg', 'alt' => '(icebrown)', 'width' => 30, 'height' => 29],
		'operation-puffle-epf-logo' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/operation-puffle-epf-logo.svg', 'alt' => '(epf)', 'width' => 30, 'height' => 29],
		'strawberry-ice-cream' => ['src' => 'https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/strawberry-ice-cream.svg', 'alt' => '(icepink)', 'width' => 30, 'height' => 29],
	];

	/** @var array<string, string> */
	public static $emoticons = [
		'😕' => 'meh',
		':-/' => 'meh',
		'😠' => 'angry',
		'>:(' => 'angry',
		'>:-(' => 'angry',
		'🎂' => 'cake',
		'(cake)' => 'cake',
		'☕' => 'coffee',
		'(coffee)' => 'coffee',
		'⚘' => 'flower',
		'(flower)' => 'flower',
		'🙁' => 'frowning',
		'(frown)' => 'frowning',
		'(frowning)' => 'frowning',
		":'-(" => 'frowning',
		":'(" => 'frowning',
		'☺' => 'happy',
		':-)' => 'happy',
		':)' => 'happy',
		'♥' => 'heart',
		'<3' => 'heart',
		'(love)' => 'heart',
		'🕹' => 'joystick',
		'(game)' => 'joystick',
		'(joystick)' => 'joystick',
		'😃' => 'laughing',
		':D' => 'laughing',
		':-D' => 'laughing',
		'💡' => 'light-bulb',
		'(bulb)' => 'light-bulb',
		'🌃' => 'moon-and-stars',
		'(night)' => 'moon-and-stars',
		'🍕' => 'pizza',
		'(pizza)' => 'pizza',
		'😹' => 'rabbit-face-with-tears-of-joy',
		'(joy)' => 'rabbit-face-with-tears-of-joy',
		":'D" => 'rabbit-face-with-tears-of-joy',
		":'-D" => 'rabbit-face-with-tears-of-joy',
		'😻' => 'smiling-cat-face-with-heart-shaped-eyes',
		'(cat)' => 'smiling-cat-face-with-heart-shaped-eyes',
		'☹' => 'sad',
		':-(' => 'sad',
		':(' => 'sad',
		'☘' => 'shamrock',
		'(clover)' => 'shamrock',
		'😝' => 'sticking-out-tongue',
		':-p' => 'sticking-out-tongue',
		':-P' => 'sticking-out-tongue',
		':p' => 'sticking-out-tongue',
		':P' => 'sticking-out-tongue',
		'😐' => 'straight',
		':-|' => 'straight',
		':|' => 'straight',
		'🌣' => 'sun',
		'☀' => 'sun',
		'☼' => 'sun',
		'(sun)' => 'sun',
		'😮' => 'surprised',
		':-o' => 'surprised',
		':-O' => 'surprised',
		':o' => 'surprised',
		':O' => 'surprised',
		'🎵' => 'toot',
		'(toot)' => 'toot',
		'😉' => 'winking',
		';-)' => 'winking',
		';)' => 'winking',
		'*@*' => 'puffle',
		'(puffle)' => 'puffle',
		'(coin)' => 'coin',
		'(icebrown)' => 'chocolate-ice-cream',
		'(epf)' => 'operation-puffle-epf-logo',
		'(icepink)' => 'strawberry-ice-cream',
	];

	private DocParser $parser;

	private HtmlRenderer $htmlRenderer;

	public function __construct(
		private PageRepository $pages,
		private HTMLPurifier $purifier,
		OEmbedFactory $oembed,
	) {
		$environment = Environment::createCommonMarkEnvironment();
		$environment->addInlineParser(new EmoticonParser(self::$images, self::$emoticons));
		$environment->addBlockParser(new OembedParser($oembed, self::$OEMBED_WHITELIST));
		$environment->addBlockParser(new SpoilerParser());
		$environment->addBlockRenderer(OembedBlock::class, new OembedRenderer());
		$environment->addBlockRenderer(Spoiler::class, new SpoilerRenderer());
		$this->parser = new DocParser($environment);
		$this->htmlRenderer = new HtmlRenderer($environment);
	}

	/**
	 * @return array{
	 *   text: string,
	 *   errors: array<array{
	 *     0: int,
	 *     1: int,
	 *     2: string,
	 *     3: array<mixed>,
	 *   }>,
	 * }
	 */
	public function format($markdown): array {
		$markdown = $this->replaceGalleries($markdown);

		$markdown = $this->replaceProps($markdown);

		$markdown = $this->replaceWikiLinks($markdown);

		$markdown = $this->replaceCustomTags($markdown);

		$document = $this->parser->parse($markdown);

		$text = $this->htmlRenderer->renderBlock($document);

		$text = $this->purifier->purify($text);

		return ['text' => $text, 'errors' => $this->purifier->context->get('ErrorCollector')->getRaw()];
	}

	public function replaceGalleries($text): array|string|null {
		$imageSize = function($url, $thumbUrl): array|false {
			$size = @getimagesize($thumbUrl);
			if (!$size) {
				$size = @getimagesize($url);
			}

			return $size;
		};
		$text = preg_replace_callback('/<gallery type="carousel">(.+?)<\/gallery>/s', function(array $match) use ($imageSize): string {
			$temp = sha1((string) random_int(0, mt_getrandmax()));
			$maxWidth = $maxHeight = 0;
			$images = preg_replace_callback('/!\[(.*?)\]\(([^"]+?)(?: "([^"]+)")?\)/', function($match) use ($imageSize, &$maxWidth, &$maxHeight): string {
				$alt = $match[1];
				$caption = !empty($match[3]) ? '<div class="carousel-caption"><p>' . $match[3] . '</p></div>' : '';
				$url = $match[2];
				$thumbUrl = preg_replace('/\.(png|jp[e]g|gif)$/', '.thumb.$1', $url);
				[$width, $height] = $imageSize($url, $thumbUrl);
				$code = '<div class="item' . ($maxHeight == 0 ? ' active' : '') . '"><a href="' . $url . '" data-lightbox="true"><img src="' . $thumbUrl . '" alt="' . $alt . '" width="' . $width . '" height="' . $height . '"></a>' . $caption . '</div>' . \PHP_EOL;
				$maxWidth = max($maxWidth, $width);
				$maxHeight = max($maxHeight, $height);

				return $code;
			}, (string) $match[1]);

			return <<<EOT
				<figure>
				<div id="carousel-{$temp}" class="carousel slide" style="width: {$maxWidth}px; height: {$maxHeight}px;" data-interval="false">
				<div class="carousel-inner">
				{$images}
				</div>
				<a href="#carousel-{$temp}" data-slide="prev" class="left carousel-control">‹</a>
				<a href="#carousel-{$temp}" data-slide="next" class="right carousel-control">›</a>
				</div>
				</figure>
				EOT;
		}, (string) $text);

		return $text;
	}

	public function replaceProps($text): string|array|null {
		$text = preg_replace('/<prop>vystavba<\/prop>/i', '<figure><img src="http://cdn.fan-club-penguin.cz/img/vystavba.gif"></figure>', (string) $text);
		$text = preg_replace('/<prop>fieldop<\/prop>/i', '<figure><img alt="Field-op" src="http://upload.fan-club-penguin.cz/files/system/phone-red-pulsing-big.gif"></figure>', (string) $text);
		$text = preg_replace('/<prop>message<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-blue-pulsing-big.gif"></figure>', (string) $text);
		$text = preg_replace('/<prop>message2013<\/prop>/i', '<figure><img alt="Zpráva" src="http://upload.fan-club-penguin.cz/files/system/phone-2013.png" width="146" height="200"></figure>', (string) $text);
		$text = preg_replace('/<prop>sponsored<\/prop>/i', '<img title="Sponsorovaná párty" alt="Sponsorovaná párty" src="http://cdn.fan-club-penguin.cz/img/sponsored.png" width="14" height="14">', (string) $text);
		$text = preg_replace('/<prop>multiclip<\/prop>/i', '<span class="icon-random" title="Více klipů náhodně míchaných při přehrávání"></span>', (string) $text);

		return $text;
	}

	public function replaceCustomTags($text): ?string {
		$text = CustomTags::age($text);
		$text = CustomTags::item($text);
		$text = CustomTags::coins($text);
		$text = CustomTags::music($text);

		return $text;
	}

	public function replaceWikiLinks($text): array|string|null {
		$text = preg_replace_callback('~\[\[([^\]|\n]+)(?:\|([^\]|\n]+))?\]\]~u', function(array $matches): string {
			$link = $label = $matches[1];
			if (\count($matches) === 3) {
				$label = $matches[2];
			}

			$link = Strings::webalize($link, '/');

			$redlink = $this->pages->findBy(['slug' => $link])->countStored() === 0;

			return '<a href="page:' . $link . '"' . ($redlink ? ' class="redlink"' : '') . '>' . $label . '</a>';
		}, (string) $text);

		return $text;
	}

	public function formatErrors($errors): Html {
		$list = Html::el('ul');
		foreach ($errors as $error) {
			$list->addHtml(Html::el('li', 'Na řádku ' . $error[0] . ': ' . $error[2]));
		}

		return $list;
	}
}
