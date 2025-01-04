<?php

declare(strict_types=1);

namespace App\Helpers\Formatting;

use App\Helpers\Formatting\Extension\CommonMarkChatExtension;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;

class ChatFormatter {
	private readonly CommonMarkConverter $converter;

	public function __construct(ChatRepository $chats, HelperLoader $helperLoader) {
		$environment = new Environment();
		$environment->addExtension(new CommonMarkChatExtension($chats, $helperLoader));
		$config = [
			'html_input' => 'escape',
		];
		$this->converter = new CommonMarkConverter($config, $environment);
	}

	public function format($markdown): string {
		return $this->converter->convertToHtml($markdown);
	}
}
