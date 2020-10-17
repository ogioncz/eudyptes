<?php

namespace App\Model;

use Nette;
use DateTimeImmutable;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * User
 * @property int $id {primary}
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $role {default basic}
 * @property bool $member {default false}
 * @property bool $notifyByMail {default true}
 * @property string|null $skype
 * @property DateTimeImmutable|null $registered {default now}
 * @property DateTimeImmutable $lastActivity {default now}
 * @property string|null $profile
 *
 * @property OneHasMany|Token[] $tokens {1:m Token::$user}
 * @property OneHasMany|Post[] $createdPosts {1:m Post::$user, orderBy=[createdAt, DESC]}
 * @property OneHasMany|Page[] $createdPages {1:m Page::$user, orderBy=[title, DESC]}
 * @property OneHasMany|Revision[] $createdRevisions {1:m Revision::$user, orderBy=[timestamp, DESC]}
 * @property OneHasMany|PostRevision[] $createdPostRevisions {1:m PostRevision::$user, orderBy=[timestamp, DESC]}
 * @property OneHasMany|Mail[] $receivedMail {1:m Mail::$recipient, orderBy=[timestamp, DESC]}
 * @property OneHasMany|Mail[] $sentMail {1:m Mail::$sender, orderBy=[timestamp, DESC]}
 * @property OneHasMany|Chat[] $createdChats {1:m Chat::$user, orderBy=[timestamp, DESC]}
 * @property OneHasMany|Meeting[] $createdMeetings {1:m Meeting::$user, orderBy=[date, DESC]}
 * @property ManyHasMany|Meeting[] $visitedMeetings {m:m Meeting::$visitors}
 * @property ManyHasMany|Stamp[] $ownedStamps {m:m Stamp::$owners , isMain=true}
 */
class User extends Entity implements Nette\Security\IRole, Nette\Security\IResource {
	public function getRoleId() {
		return $this->role;
	}

	public function getResourceId() {
		return 'user';
	}
}
