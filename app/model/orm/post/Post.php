<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Post
 * @property User $user {m:1 UserRepository $createdPosts}
 * @property string $title {virtual}
 * @property string $markdown {virtual}
 * @property string $content {virtual}
 * @property DateTime $createdAt {default now}
 * @property bool $likeable {default false}
 * @property bool $published {default true}
 *
 * @property-read PostRevision $lastRevision {virtual}
 * @property OneHasMany|PostRevision[] $revisions {1:m PostRevisionRepository order:timestamp,DESC}
 */
class Post extends Entity implements Nette\Security\IResource {
	public function getterLastRevision() {
		return $this->revisions->get()->orderBy(['timestamp' => 'DESC'])->fetch();
	}

	public function getterTitle() {
		return $this->lastRevision ? $this->lastRevision->title : null;
	}

	public function getterMarkdown() {
		return $this->lastRevision ? $this->lastRevision->markdown : null;
	}

	public function getterContent() {
		return $this->lastRevision ? $this->lastRevision->content : null;
	}

	public function getResourceId() {
		return 'post';
	}
}
