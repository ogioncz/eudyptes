<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nette;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Post
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdPosts}
 * @property string $title {virtual}
 * @property string $markdown {virtual}
 * @property string $content {virtual}
 * @property DateTimeImmutable $createdAt {default now}
 * @property bool $likeable {default false}
 * @property bool $published {default true}
 *
 * @property-read PostRevision $lastRevision {virtual}
 * @property OneHasMany|PostRevision[] $revisions {1:m PostRevision::$post, orderBy=[timestamp, DESC]}
 */
class Post extends Entity implements Nette\Security\Resource {
	public function getterLastRevision(): PostRevision {
		return $this->revisions->get()->orderBy(['timestamp' => 'DESC'])->fetch();
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

	public function getResourceId(): string {
		return 'post';
	}
}
