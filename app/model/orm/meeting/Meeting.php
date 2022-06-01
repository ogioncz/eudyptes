<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nette;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * Meeting.
 *
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdMeetings}
 * @property string $title
 * @property string $server
 * @property DateTimeImmutable $date
 * @property string $program
 * @property string $markdown
 * @property string $description
 * @property string $ip
 * @property ManyHasMany|User[] $visitors {m:m User::$visitedMeetings, isMain=true}
 */
class Meeting extends Entity implements Nette\Security\Resource {
	public function getResourceId(): string {
		return 'meeting';
	}
}
