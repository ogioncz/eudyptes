<?php

namespace App\Model;

class RevisionMapper extends BaseMapper {
	public function getTableName(): string {
		return 'page_revision';
	}
}
