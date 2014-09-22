<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;
use Nextras\Orm\Entity\Collection\ICollection;

class MeetingRepository extends Repository {
	public function findUpcoming() {
		return $this->findAll()->findBy(['date>=' => new \DateTime('today')])->orderBy(['date' => ICollection::ASC, 'start' => ICollection::ASC]);
	}
}
