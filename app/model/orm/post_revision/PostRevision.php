<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;

/**
 * PostRevision.
 *
 * @property int $id {primary}
 * @property Post $post {m:1 Post::$revisions}
 * @property string $title
 * @property string $markdown
 * @property string $content
 * @property DateTimeImmutable|null $timestamp {default now}
 * @property User $user {m:1 User::$createdPostRevisions}
 * @property string $ip
 * @property-read Revision|null $previous {virtual}
 * @property-read Revision|null $next {virtual}
 */
class PostRevision extends Entity {
	public function getterPrevious(): ?self {
		return $this->getRepository()->findBy(['post' => $this->post->id, 'id<' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::DESC])->limitBy(1)->fetch();
	}

	public function getterNext(): ?self {
		return $this->getRepository()->findBy(['post' => $this->post->id, 'id>' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::ASC])->limitBy(1)->fetch();
	}
}
