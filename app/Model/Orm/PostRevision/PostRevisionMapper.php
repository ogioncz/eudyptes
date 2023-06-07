<?php

declare(strict_types=1);

namespace App\Model\Orm\PostRevision;

use App\Model\Orm\BaseMapper;

class PostRevisionMapper extends BaseMapper {
	public function getTableName(): string {
		return 'post_revision';
	}
}
