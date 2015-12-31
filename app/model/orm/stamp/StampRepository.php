<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class StampRepository extends Repository {
	public static function getEntityClassNames() {
		return [Stamp::class];
	}
}
