<?php

declare(strict_types=1);

namespace App\Model\Orm\Revision;

use Nextras\Orm\Repository\Repository;
use Override;

class RevisionRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Revision::class];
	}
}
