<?php

declare(strict_types=1);

namespace App\Model\Orm\Page;

use Nextras\Orm\Repository\Repository;

class PageRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Page::class];
	}
}
