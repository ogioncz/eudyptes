<?php

declare(strict_types=1);

namespace App\Model\Orm\Token;

use Nextras\Orm\Repository\Repository;

class TokenRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Token::class];
	}
}
