<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class TokenRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Token::class];
	}
}
