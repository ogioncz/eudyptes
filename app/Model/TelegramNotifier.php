<?php

declare(strict_types=1);

namespace App\Model;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class TelegramNotifier {
	public function __construct(string $apiKey, private readonly int $chatId, string $botName) {
		new Telegram($apiKey, $botName);
	}

	public function chatMessage(string $username, string $message): ServerResponse {
		$data = ['chat_id' => $this->chatId, 'text' => "{$username} píše na webu: „{$message}“"];

		return Request::sendMessage($data);
	}
}
