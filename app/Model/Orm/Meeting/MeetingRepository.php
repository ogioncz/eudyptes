<?php

declare(strict_types=1);

namespace App\Model\Orm\Meeting;

use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;

class MeetingRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Meeting::class];
	}

	public function findUpcoming(): ICollection {
		return $this->findAll()->findBy(['date>=' => new \DateTimeImmutable('today')])->orderBy(['date' => ICollection::ASC]);
	}
}
