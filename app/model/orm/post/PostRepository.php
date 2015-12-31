<?php

namespace App\Model;

use Nextras\Orm\Repository\Repository;

class PostRepository extends Repository {
	public static function getEntityClassNames() {
		return [Post::class];
	}
}
