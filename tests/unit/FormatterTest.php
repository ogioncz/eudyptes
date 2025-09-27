<?php

declare(strict_types=1);

namespace Test;

use Alb\OEmbed\Response as OEmbedResponse;
use Alb\OEmbed\Simple as OEmbed;
use App\Helpers\Formatting\Formatter;
use App\Model\Orm\Page\PageRepository;
use HTMLPurifier;
use HTMLPurifier_Context;
use HTMLPurifier_ErrorCollector;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class FormatterTest extends TestCase {
	private static function makeFormatter(): Formatter {
		$oembedResponse = Mockery::mock(OEmbedResponse::class);
		$oembedResponse->shouldReceive('getHtml')->andReturn('<video src="nggyu.webm"></video>');
		$errorCollector = Mockery::mock(HTMLPurifier_ErrorCollector::class);
		$errorCollector->shouldReceive('getRaw')->andReturn([]);
		$purifierContext = Mockery::mock(HTMLPurifier_Context::class);
		$purifierContext->shouldReceive('get')->with('ErrorCollector')->andReturn($errorCollector);
		$pages = Mockery::mock(PageRepository::class);
		$purifier = Mockery::mock(HTMLPurifier::class);
		$purifier->context = $purifierContext;
		$purifier->shouldReceive('purify')->withAnyArgs()->andReturnUsing(fn($text) => $text);
		$oembed = Mockery::mock(OEmbed::class);
		$oembed->shouldReceive('request')->with('https://youtu.be/dQw4w9WgXcQ')->andReturn($oembedResponse);

		return new Formatter($pages, $purifier, $oembed);
	}

	public function testPlain(): void {
		$markdown = 'Hello';
		$html = "<p>Hello</p>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testSpoiler(): void {
		$markdown = "¡¡¡\nHello\n!!!";
		$html = "<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Hello</p></details>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testSpoilerSummary(): void {
		$markdown = "¡¡¡ Click to open\nHello\n!!!";
		$html = "<details><summary>Click to open</summary>\n<p>Hello</p></details>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testSpoilerNested(): void {
		$markdown = "¡¡¡\n¡¡¡\n¡¡¡\nHello\n!!!\n!!!\n!!!";
		$html = "<details><summary>Pro zobrazení zápletky klikni</summary>\n<details><summary>Pro zobrazení zápletky klikni</summary>\n<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Hello</p></details></details></details>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testSpoilerNestedInText(): void {
		$markdown = "¡¡¡\n1\n¡¡¡\n2\n¡¡¡\nHello\n!!!\n3\n!!!\n4\n!!!";
		$html = "<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>1</p>\n<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>2</p>\n<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Hello</p></details>\n<p>3</p></details>\n<p>4</p></details>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testMultipleSpoilers(): void {
		$markdown = "¡¡¡\nHello\n!!!\n¡¡¡\nBye\n!!!";
		$html = "<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Hello</p></details>\n<details><summary>Pro zobrazení zápletky klikni</summary>\n<p>Bye</p></details>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testEmoji(): void {
		$markdown = ':-)';
		$html = "<p><img src=\"https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg\" alt=\"\" width=\"30\" height=\"29\" /></p>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testOembed(): void {
		$markdown = 'https://youtu.be/dQw4w9WgXcQ';
		$html = "<figure class=\"rwd-media rwd-ratio-16-9\"><video src=\"nggyu.webm\"></video></figure>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testOembedInText(): void {
		$markdown = "Hello\nhttps://youtu.be/dQw4w9WgXcQ\nGood-by";
		$html = "<p>Hello</p>\n<figure class=\"rwd-media rwd-ratio-16-9\"><video src=\"nggyu.webm\"></video></figure>\n<p>Good-by</p>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}

	public function testYoutubeUrlInParagraph(): void {
		$markdown = 'You must see https://youtu.be/dQw4w9WgXcQ';
		$html = "<p>You must see https://youtu.be/dQw4w9WgXcQ</p>\n";

		$formatted = self::makeFormatter()->format($markdown);
		Assert::equal($html, $formatted['text']);
		Assert::equal([], $formatted['errors']);
	}
}

$test = new FormatterTest();
$test->run();
