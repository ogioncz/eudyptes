<?php

declare(strict_types=1);

namespace App\Model\Orm\Meeting;

use App\Model\Orm\User\User;
use DateTimeImmutable;
use Nette\Security\Resource;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;
use Override;

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
 * @property ManyHasMany<User> $visitors {m:m User::$visitedMeetings, isMain=true}
 */
class Meeting extends Entity implements Resource {
	#[Override]
	public function getResourceId(): string {
		return 'meeting';
	}
}
