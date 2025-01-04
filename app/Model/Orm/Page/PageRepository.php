<?php

declare(strict_types=1);

namespace App\Model\Orm\Page;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Page>
 */
class PageRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Page::class];
	}
}
