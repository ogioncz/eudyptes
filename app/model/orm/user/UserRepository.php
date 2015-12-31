<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Repository\Repository;

class UserRepository extends Repository {
	public static function getEntityClassNames() {
		return [User::class];
	}

	public function findActive(DateTime $time = null) {
		if (!$time) {
			$time = new DateTime('-5 min');
		}
		return $this->findBy(['lastActivity>=' => $time]);
	}
}
