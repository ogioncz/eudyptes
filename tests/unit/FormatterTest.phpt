<?php

namespace Test;

use App\Helpers\Formatting\Formatter;
use Mockery;
use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';

class FormatterTest extends Tester\TestCase {
	private $formatter;

	function setUp() {
		$oembedResponse = Mockery::mock('Alb\OEmbed\Response');
		$oembedResponse->shouldReceive('getHtml')->andReturn('<video src="nggyu.webm"></video>');
		$errorCollector = Mockery::mock('HTMLPurifier_ErrorCollector');
		$errorCollector->shouldReceive('getRaw')->andReturn([]);
		$purifierContext = Mockery::mock('HTMLPurifier_Context');
		$purifierContext->shouldReceive('get')->with('ErrorCollector')->andReturn($errorCollector);
		$pages = Mockery::mock('App\Model\PageRepository');
		$purifier = Mockery::mock('HTMLPurifier');
		$purifier->context = $purifierContext;
		$purifier->shouldReceive('purify')->withAnyArgs()->andReturnUsing(function($text) {
			return $text;
		});
		$oembed = Mockery::mock('Alb\OEmbed\Simple');
		$oembed->shouldReceive('request')->with('https://youtu.be/dQw4w9WgXcQ')->andReturn($oembedResponse);
		$this->formatter = new Formatter($pages, $purifier, $oembed);
	}

	function testPlain() {
		$markdown = 'Hello';
		$html = "<p>Hello</p>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testSpoiler() {
		$markdown = "¡¡¡\nHello\n!!!";
		$html = "<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Hello</p></details>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testSpoilerSummary() {
		$markdown = "¡¡¡ Click to open\nHello\n!!!";
		$html = "<details><summary>Click to open</summary>\n<p>Hello</p></details>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testEmoji() {
		$markdown = ':-)';
		$html = "<p><img src=\"https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg\" alt=\"\" width=\"30\" height=\"29\" /></p>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testOembed() {
		$markdown = "https://youtu.be/dQw4w9WgXcQ";
		$html = "<figure class=\"rwd-media rwd-ratio-16-9\"><video src=\"nggyu.webm\"></video></figure>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testOembedInText() {
		$markdown = "Hello\nhttps://youtu.be/dQw4w9WgXcQ\nGood-by";
		$html = "<p>Hello</p>\n<figure class=\"rwd-media rwd-ratio-16-9\"><video src=\"nggyu.webm\"></video></figure>\n<p>Good-by</p>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}

	function testYoutubeUrlInParagraph() {
		$markdown = "You must see https://youtu.be/dQw4w9WgXcQ";
		$html = "<p>You must see https://youtu.be/dQw4w9WgXcQ</p>\n";
		$ret = ['text' => $html, 'errors' => []];
		Assert::equal($ret, $this->formatter->format($markdown));
	}
}

$test = new FormatterTest($container);
$test->run();
