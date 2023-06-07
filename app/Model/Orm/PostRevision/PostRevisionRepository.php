<?php

declare(strict_types=1);

namespace App\Model\Orm\PostRevision;

use Nextras\Orm\Repository\Repository;

class PostRevisionRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [PostRevision::class];
	}
}
