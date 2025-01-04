<?php

declare(strict_types=1);

namespace App\Model\Orm\Chat;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Chat>
 */
class ChatRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Chat::class];
	}
}
