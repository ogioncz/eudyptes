<?php

namespace App\Model;

class MailMapper extends BaseMapper {
	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();
		$reflection->addMapping('reaction', 'reaction');
		$reflection->addMapping('recipient', 'recipient');
		$reflection->addMapping('sender', 'sender');
		return $reflection;
	}
}
