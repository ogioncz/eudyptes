<?php

namespace App\Model;

use Nextras\Orm\Mapper\Mapper;
use Nextras\Orm\Mapper\IMapper;

class MailMapper extends BaseMapper {
	protected function createStorageReflection() {
		$reflection = parent::createStorageReflection();
		$reflection->addMapping('reaction', 'reaction');
		return $reflection;
	}
}
