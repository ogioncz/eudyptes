<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;
use Nextras\Orm\Collection\ICollection;

class MeetingRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Meeting::class];
	}

	public function findUpcoming() {
		return $this->findAll()->findBy(['date>=' => new \DateTimeImmutable('today')])->orderBy(['date' => ICollection::ASC]);
	}
}
