<?php

declare(strict_types=1);

namespace App\Helpers\Formatting;

use App\Helpers\Formatting\Extension\CommonMarkChatExtension;
use App\Model\HelperLoader;
use App\Model\Orm\Chat\ChatRepository;
use League\CommonMark\Environment;
use League\CommonMark\MarkdownConverter;

class ChatFormatter {
	private readonly MarkdownConverter $converter;

	public function __construct(ChatRepository $chats, HelperLoader $helperLoader) {
		$environment = new Environment([
			'html_input' => 'escape',
		]);
		$environment->addExtension(new CommonMarkChatExtension($chats, $helperLoader));

		$this->converter = new MarkdownConverter($environment);
	}

	public function format(string $markdown): string {
		return $this->converter->convertToHtml($markdown);
	}
}
