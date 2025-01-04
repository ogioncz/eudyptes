<?php

declare(strict_types=1);

namespace App\Model\Orm\Stamp;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Stamp>
 */
class StampRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Stamp::class];
	}
}
