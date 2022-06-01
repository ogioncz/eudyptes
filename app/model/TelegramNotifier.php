<?php

declare(strict_types=1);

namespace App\Model;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class TelegramNotifier {
	public function __construct(string $apiKey, private int $chatId, string $botName) {
		new Telegram($apiKey, $botName);
	}

	public function chatMessage($username, $message) {
		$data = ['chat_id' => $this->chatId, 'text' => "${username} píše na webu: „${message}“"];

		return Request::sendMessage($data);
	}
}
