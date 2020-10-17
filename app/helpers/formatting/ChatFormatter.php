<?php

namespace App\Helpers\Formatting;

use App\Helpers\Formatting\Extension\CommonMarkChatExtension;
use App\Model\ChatRepository;
use App\Model\HelperLoader;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Nette;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class ChatFormatter {
	use Nette\SmartObject;

	/** @var ChatRepository */
	public $chats;

	/** @var HelperLoader */
	public $helperLoader;

	/** @var CommonMarkConverter */
	private $converter;

	public function __construct(ChatRepository $chats, HelperLoader $helperLoader) {
		$this->chats = $chats;
		$this->helperLoader = $helperLoader;

		$environment = new Environment();
		$environment->addExtension(new CommonMarkChatExtension($this->chats, $helperLoader));
		$config = [
			'html_input' => 'escape'
		];
		$this->converter = new CommonMarkConverter($config, $environment);
	}

	public function format($markdown) {
		return $this->converter->convertToHtml($markdown);
	}
}
