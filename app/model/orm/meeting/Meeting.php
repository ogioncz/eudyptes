<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * Meeting
 * @property User $user {m:1 UserRepository $createdMeetings}
 * @property string $title
 * @property string $server
 * @property DateTime $date
 * @property string $program
 * @property string $markdown
 * @property string $description
 * @property string $ip
 *
 * @property ManyHasMany|User[] $visitors {m:n UserRepository $visitedMeetings primary}
 */
class Meeting extends Entity implements Nette\Security\IResource {
	public function getResourceId() {
		return 'meeting';
	}
}
