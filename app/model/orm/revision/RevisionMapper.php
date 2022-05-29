<?php

declare(strict_types=1);

namespace App\Model;

class RevisionMapper extends BaseMapper {
	public function getTableName(): string {
		return 'page_revision';
	}
}
