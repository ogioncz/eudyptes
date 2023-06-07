<?php

declare(strict_types=1);

namespace App\Model\Orm\Revision;

use App\Model\Orm\BaseMapper;

class RevisionMapper extends BaseMapper {
	public function getTableName(): string {
		return 'page_revision';
	}
}
