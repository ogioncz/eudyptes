<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Page
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdPages}
 * @property string $slug
 * @property string $title
 * @property-read string $markdown {virtual}
 * @property-read string $redirect {virtual}
 * @property string|null $icon
 * @property bool $menu {default false}
 *
 * @property-read Revision $lastRevision {virtual}
 * @property OneHasMany|Revision[] $revisions {1:m Revision::$page, orderBy=[timestamp, DESC]}
 */
class Page extends Entity implements Nette\Security\IResource {
	public function getterLastRevision() {
		return $this->revisions->get()->orderBy(['timestamp' => 'DESC'])->fetch();
	}

	public function getterMarkdown() {
		return $this->lastRevision ? $this->lastRevision->markdown : null;
	}

	public function getterRedirect() {
		return $this->lastRevision ? $this->lastRevision->redirect : null;
	}

	public function getResourceId() {
		return 'page';
	}
}
