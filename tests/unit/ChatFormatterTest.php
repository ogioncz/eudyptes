<?php

declare(strict_types=1);

namespace Test;

use App\Helpers\Formatting\ChatFormatter;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\Chat;
use App\Model\Orm\Chat\ChatRepository;
use App\Model\Orm\User\User;
use Mockery;
use Nette\Utils\Html;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ChatFormatterTest extends TestCase {
	private ?ChatFormatter $formatter = null;

	#[Override]
	protected function setUp(): void {
		$adam = Mockery::mock(User::class);
		$eve = Mockery::mock(User::class);
		$chat1 = Mockery::mock(Chat::class);
		$chat1->shouldReceive('getValue')->with('content')->andReturn('<p>Ping</p>');
		$chat1->shouldReceive('getValue')->with('user')->andReturn($adam);
		$chat2 = Mockery::mock(Chat::class);
		$chat2->shouldReceive('getValue')->with('content')->andReturn("<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n<p>Pong</p>");
		$chat2->shouldReceive('getValue')->with('user')->andReturn($eve);
		$chats = Mockery::mock(ChatRepository::class);
		$chats->shouldReceive('getById')->with(1)->andReturn($chat1);
		$chats->shouldReceive('getById')->with(2)->andReturn($chat2);
		$helperLoader = Mockery::mock(HelperLoader::class);
		$helperLoader->shouldReceive('userLink')->with($adam, true)->andReturn(Html::fromHtml('<a href="adam">Adam</a>'));
		$helperLoader->shouldReceive('userLink')->with($eve, true)->andReturn(Html::fromHtml('<a href="adam">Eve</a>'));
		$this->formatter = new ChatFormatter($chats, $helperLoader);
	}

	public function testPlain(): void {
		$markdown = 'Hello';
		$html = "<p>Hello</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testEmoji(): void {
		$markdown = 'Hello :-)';
		$html = "<p>Hello <img src=\"https://cdn.rawgit.com/ogioncz/club-penguin-emoji/master/happy.svg\" alt=\"\" width=\"30\" height=\"29\" /></p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testMultiline(): void {
		$markdown = "Hello\n\nWorld";
		$html = "<p>Hello</p>\n<p>World</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testUrl(): void {
		$markdown = 'http://www.example.com';
		$html = "<p><a href=\"http://www.example.com\">http://www.example.com</a></p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testUrlText(): void {
		$markdown = 'Go to http://www.example.com for example';
		$html = "<p>Go to <a href=\"http://www.example.com\">http://www.example.com</a> for example</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testStrongDisabled(): void {
		$markdown = '**important**';
		$html = "<p>**important**</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testHtmlEscaped(): void {
		$markdown = '<strong>important</strong>';
		$html = "<p>&lt;strong&gt;important&lt;/strong&gt;</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testQuote(): void {
		$markdown = '{#1}';
		$html = "<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testQuoteText(): void {
		$markdown = "{#1}\nHello";
		$html = "<blockquote><strong><a href=\"adam\">Adam</a></strong>\n<p>Ping</p></blockquote>\n<p>Hello</p>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}

	public function testQuoteQuoted(): void {
		$markdown = '{#2}';
		$html = "<blockquote><strong><a href=\"adam\">Eve</a></strong>\n<p>Pong</p></blockquote>\n";
		Assert::equal($html, $this->formatter->format($markdown));
	}
}

$test = new ChatFormatterTest();
$test->run();
