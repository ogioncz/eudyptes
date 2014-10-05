<?php

namespace App\Model;

class UserMapper extends BaseMapper {
	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();
		$reflection->addMapping('notifyByMail', 'mailnotify');
		return $reflection;
	}
}
