<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Collection\ICollection;

/**
 * Revision
 * @property Post $post {m:1 PostRepository $revisions}
 * @property string $title
 * @property string $markdown
 * @property string $content
 * @property DateTime|null $timestamp {default now}
 * @property User $user {m:1 UserRepository $createdPostRevisions}
 * @property string $ip
 *
 * @property-read Revision|null $previous {virtual}
 * @property-read Revision|null $next {virtual}
 */
class PostRevision extends Entity {
	public function getterPrevious() {
		return $this->getRepository()->findBy(['post' => $this->post->id, 'id<' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::DESC])->limitBy(1)->fetch();
	}

	public function getterNext() {
		return $this->getRepository()->findBy(['post' => $this->post->id, 'id>' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::ASC])->limitBy(1)->fetch();
	}
}
