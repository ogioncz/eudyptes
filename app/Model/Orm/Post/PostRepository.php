<?php

declare(strict_types=1);

namespace App\Model\Orm\Post;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Post>
 */
class PostRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Post::class];
	}
}
