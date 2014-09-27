<?php

namespace App\Model;

use Nextras\Orm\Mapper\Mapper;


class BaseMapper extends Mapper {
	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();
		$reflection->manyHasManyStorageNamePattern = '%s_%s';
		return $reflection;
	}
}
