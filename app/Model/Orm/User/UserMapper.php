<?php

declare(strict_types=1);

namespace App\Model\Orm\User;

use App\Model\Orm\BaseMapper;
use Nextras\Orm\Mapper\Dbal\Conventions\IConventions;

class UserMapper extends BaseMapper {
	protected function createConventions(): IConventions {
		$conventions = parent::createConventions();
		$conventions->addMapping('notifyByMail', 'mailnotify');

		return $conventions;
	}
}
