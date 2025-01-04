<?php

declare(strict_types=1);

namespace App\Model\Orm\Token;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Token>
 */
class TokenRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Token::class];
	}
}
