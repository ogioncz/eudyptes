<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class PageRepository extends Repository {
	public static function getEntityClassNames() {
		return [Page::class];
	}
}
