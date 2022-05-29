<?php

declare(strict_types=1);

namespace App\Model;

class PostRevisionMapper extends BaseMapper {
	public function getTableName(): string {
		return 'post_revision';
	}
}
