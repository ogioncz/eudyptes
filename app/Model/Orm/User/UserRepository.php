<?php

declare(strict_types=1);

namespace App\Model\Orm\User;

use DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;
use Override;

class UserRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [User::class];
	}

	public function findActive(?DateTimeImmutable $time = null): ICollection {
		if (!$time) {
			$time = new DateTimeImmutable('-5 min');
		}

		return $this->findBy(['lastActivity>=' => $time]);
	}
}
