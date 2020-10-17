<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class RevisionRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Revision::class];
	}
}
