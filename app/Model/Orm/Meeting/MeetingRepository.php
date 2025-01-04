<?php

declare(strict_types=1);

namespace App\Model\Orm\Meeting;

use DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Meeting>
 */
class MeetingRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Meeting::class];
	}

	public function findUpcoming(): ICollection {
		return $this->findAll()->findBy(['date>=' => new DateTimeImmutable('today')])->orderBy(['date' => ICollection::ASC]);
	}
}
