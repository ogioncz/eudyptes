<?php

declare(strict_types=1);

namespace App\Model\Orm\Page;

use App\Model\Orm\Revision\Revision;
use App\Model\Orm\User\User;
use Nette\Security\Resource;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Page.
 *
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdPages}
 * @property string $slug
 * @property string $title
 * @property-read string $markdown {virtual}
 * @property-read string $redirect {virtual}
 * @property string|null $icon
 * @property bool $menu {default false}
 * @property-read Revision $lastRevision {virtual}
 * @property OneHasMany|Revision[] $revisions {1:m Revision::$page, orderBy=[timestamp, DESC]}
 */
class Page extends Entity implements Resource {
	public function getterLastRevision(): Revision {
		return $this->revisions->get()->orderBy(['timestamp' => 'DESC'])->fetch();
	}

	public function getterMarkdown(): string {
		return $this->lastRevision ? $this->lastRevision->markdown : null;
	}

	public function getterRedirect(): ?string {
		return $this->lastRevision ? $this->lastRevision->redirect : null;
	}

	public function getResourceId(): string {
		return 'page';
	}
}
