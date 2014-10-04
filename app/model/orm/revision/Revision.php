<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Revision
 * @property Page $page {m:1 PageRepository $revisions}
 * @property string $markdown
 * @property string $content
 * @property DateTime $timestamp {default now}
 * @property User $user {m:1 UserRepository $createdRevisions}
 * @property string $ip
 */
class Revision extends Entity {
}
