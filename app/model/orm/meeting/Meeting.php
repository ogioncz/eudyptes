<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * Meeting
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdMeetings}
 * @property string $title
 * @property string $server
 * @property DateTime $date
 * @property string $program
 * @property string $markdown
 * @property string $description
 * @property string $ip
 *
 * @property ManyHasMany|User[] $visitors {m:n User::$visitedMeetings, isMain=true}
 */
class Meeting extends Entity implements Nette\Security\IResource {
	public function getResourceId() {
		return 'meeting';
	}
}
