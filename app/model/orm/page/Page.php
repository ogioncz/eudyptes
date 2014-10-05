<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Page
 * @property User $user {m:1 UserRepository $createdPages}
 * @property string $slug
 * @property string $title
 * @property string|null $icon
 * @property bool $menu {default false}
 *
 * @property-read Revision $lastRevision
 * @property OneHasMany|Revision[] $revisions {1:m RevisionRepository order:timestamp,DESC}
 */
class Page extends Entity {
	public function getLastRevision() {
		return $this->revisions->get()->orderBy(['timestamp' => 'DESC'])->fetch();
	}
}