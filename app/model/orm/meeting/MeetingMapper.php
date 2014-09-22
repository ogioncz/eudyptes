<?php

namespace App\Model;

use Nextras\Orm\Mapper\Mapper;
use Nextras\Orm\Mapper\IMapper;


class MeetingMapper extends Mapper {
	public function getManyHasManyParameters(IMapper $mapper) {
		if($mapper instanceof UserMapper) {
			return ['meeting_user', ['meeting_id', 'user_id']];
		}
		return parent::getManyHasManyParameters($mapper);
	}
}
