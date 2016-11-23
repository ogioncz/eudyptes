<?php

namespace App\Model;

use Nette;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class TelegramNotifier {
	use Nette\SmartObject;

	/** @var Longman\TelegramBot\Telegram */
	public $telegram;

	/** @var int */
	public $chatId;

	public function __construct($apiKey, $chatId, $botName) {
		$this->telegram = new Telegram($apiKey, $botName);
		$this->chatId = $chatId;
	}

	public function chatMessage($username, $message) {
		$data = ['chat_id' => $this->chatId, 'text' => "${username} píše na webu: „${message}“"];
		return Request::sendMessage($data);
	}
}
