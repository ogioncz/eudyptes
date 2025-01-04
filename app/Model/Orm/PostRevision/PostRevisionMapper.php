<?php

declare(strict_types=1);

namespace App\Model\Orm\PostRevision;

use App\Model\Orm\BaseMapper;
use Override;

/**
 * @extends BaseMapper<PostRevision>
 */
class PostRevisionMapper extends BaseMapper {
	#[Override]
	public function getTableName(): string {
		return 'post_revision';
	}
}
