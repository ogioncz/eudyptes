<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class MailRepository extends Repository {
	public static function getEntityClassNames() {
		return [Mail::class];
	}
}
