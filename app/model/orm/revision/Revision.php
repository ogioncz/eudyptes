<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Collection\ICollection;

/**
 * Revision
 * @property Page $page {m:1 PageRepository $revisions}
 * @property string $markdown
 * @property string $content
 * @property string|null $redirect
 * @property DateTime|null $timestamp {default now}
 * @property User $user {m:1 UserRepository $createdRevisions}
 * @property string $ip
 *
 * @property-read Revision|null $previous {virtual}
 * @property-read Revision|null $next {virtual}
 */
class Revision extends Entity {
	public function getterPrevious() {
		return $this->getRepository()->findBy(['page' => $this->page->id, 'id<' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::DESC])->limitBy(1)->fetch();
	}

	public function getterNext() {
		return $this->getRepository()->findBy(['page' => $this->page->id, 'id>' => $this->getPersistedId()])->orderBy(['timestamp' => ICollection::ASC])->limitBy(1)->fetch();
	}
}
