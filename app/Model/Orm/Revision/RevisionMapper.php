<?php

declare(strict_types=1);

namespace App\Model\Orm\Revision;

use App\Model\Orm\BaseMapper;
use Override;

/**
 * @extends BaseMapper<Revision>
 */
class RevisionMapper extends BaseMapper {
	#[Override]
	public function getTableName(): string {
		return 'page_revision';
	}
}
