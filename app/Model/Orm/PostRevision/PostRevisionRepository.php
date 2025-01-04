<?php

declare(strict_types=1);

namespace App\Model\Orm\PostRevision;

use Nextras\Orm\Repository\Repository;
use Override;

class PostRevisionRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [PostRevision::class];
	}
}
