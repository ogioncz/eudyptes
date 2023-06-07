<?php

declare(strict_types=1);

namespace App\Model\Orm\Revision;

use Nextras\Orm\Repository\Repository;

class RevisionRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Revision::class];
	}
}
