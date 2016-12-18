<?php

namespace Test;

use App\Helpers\Formatting\ChatFormatter;
use Mockery;
use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';

class ChatFormatterTest extends Tester\TestCase {
	private $formatter;

	function setUp() {
		$adam = Mockery::mock('App\Model\User');
		$eve = Mockery::mock('App\Model\User');
		$chat1 = Mockery::mock('App\Model\Chat');
		$chat1->shouldReceive('getValue')->with('content')->andReturn('<p>Ping</p>');
		$chat1->shouldReceive('getValue')->with('user')->andReturn($adam);
		$chat2 = Mockery::mock('App\Model\Chat');
		$chat2->shouldReceive('getValue')->with('content')->andReturn("<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n<p>Pong</p>");
		$chat2->shouldReceive('getValue')->with('user')->andReturn($eve);
		$chats = Mockery::mock('App\Model\ChatRepository');
		$chats->shouldReceive('getById')->with(1)->andReturn($chat1);
		$chats->shouldReceive('getById')->with(2)->andReturn($chat2);
		$helperLoader = Mockery::mock('App\Model\HelperLoader');
		$helperLoader->shouldReceive('userLink')->with($adam, true)->andReturn('<a href="adam">Adam</a>');
		$helperLoader->shouldReceive('userLink')->with($eve, true)->andReturn('<a href="adam">Eve</a>');
		$this->formatter = new ChatFormatter($chats, $helperLoader);
	}

	function testPlain() {
		$markdown = 'Hello';
		$html = "<p>Hello</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testEmoji() {
		$markdown = 'Hello :-)';
		$html = "<p>Hello <img src=\"https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg\" alt=\"\" width=\"30\" height=\"29\" /></p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testMultiline() {
		$markdown = "Hello\n\nWorld";
		$html = "<p>Hello</p>\n<p>World</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testUrl() {
		$markdown = "http://www.example.com";
		$html = "<p><a href=\"http://www.example.com\">http://www.example.com</a></p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testUrlText() {
		$markdown = "Go to http://www.example.com for example";
		$html = "<p>Go to <a href=\"http://www.example.com\">http://www.example.com</a> for example</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testStrongDisabled() {
		$markdown = "**important**";
		$html = "<p>**important**</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testHtmlEscaped() {
		$markdown = "<strong>important</strong>";
		$html = "<p>&lt;strong&gt;important&lt;/strong&gt;</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testQuote() {
		$markdown = "{#1}";
		$html = "<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testQuoteText() {
		$markdown = "{#1}\nHello";
		$html = "<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n<p>Hello</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	function testQuoteQuoted() {
		$markdown = "{#2}";
		$html = "<blockquote><strong><a href=\"adam\">Eve</a></strong>\n<p>Pong</p></blockquote>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}
}

$test = new ChatFormatterTest($container);
$test->run();
