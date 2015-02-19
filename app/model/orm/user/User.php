<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * User
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $role {default basic}
 * @property bool $member {default false}
 * @property bool $notifyByMail {default true}
 * @property string|null $skype
 * @property DateTime $registered {default now}
 * @property string|null $profile
 *
 * @property OneHasMany|Token[] $tokens {1:m TokenRepository}
 * @property OneHasMany|Post[] $createdPosts {1:m PostRepository order:timestamp,DESC}
 * @property OneHasMany|Page[] $createdPages {1:m PageRepository order:title,DESC}
 * @property OneHasMany|Revision[] $createdRevisions {1:m RevisionRepository order:timestamp,DESC}
 * @property OneHasMany|Mail[] $receivedMail {1:m MailRepository $recipient order:timestamp,DESC}
 * @property OneHasMany|Mail[] $sentMail {1:m MailRepository $sender order:timestamp,DESC}
 * @property OneHasMany|Chat[] $createdChats {1:m ChatRepository order:timestamp,DESC}
 * @property OneHasMany|Meeting[] $createdMeetings {1:m MeetingRepository order:date,DESC}
 * @property ManyHasMany|Meeting[] $visitedMeetings {m:n MeetingRepository $visitors}
 */
class User extends Entity implements Nette\Security\IRole, Nette\Security\IResource {
	public function getRoleId() {
		return $this->role;
	}

	public function getResourceId() {
		return 'user';
	}
}
