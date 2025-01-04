<?php

declare(strict_types=1);

namespace App\Model\Orm\Post;

use App\Model\Orm\PostRevision\PostRevision;
use App\Model\Orm\User\User;
use DateTimeImmutable;
use Nette\Security\Resource;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;
use Override;

/**
 * Post.
 *
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdPosts}
 * @property string $title {virtual}
 * @property string $markdown {virtual}
 * @property string $content {virtual}
 * @property DateTimeImmutable $createdAt {default now}
 * @property bool $likeable {default false}
 * @property bool $published {default true}
 * @property-read PostRevision $lastRevision {virtual}
 * @property OneHasMany<PostRevision> $revisions {1:m PostRevision::$post, orderBy=[timestamp, DESC]}
 */
class Post extends Entity implements Resource {
	public function getterLastRevision(): PostRevision {
		return $this->revisions->toCollection()->orderBy(['timestamp' => 'DESC'])->fetch();
	}

	public function getterTitle(): string {
		return $this->lastRevision ? $this->lastRevision->title : null;
	}

	public function getterMarkdown(): string {
		return $this->lastRevision ? $this->lastRevision->markdown : null;
	}

	public function getterContent(): string {
		return $this->lastRevision ? $this->lastRevision->content : null;
	}

	#[Override]
	public function getResourceId(): string {
		return 'post';
	}
}
