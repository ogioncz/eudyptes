<?php

declare(strict_types=1);

namespace App\Model\Orm;

use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\Mapper\Dbal\Conventions\Conventions;
use Nextras\Orm\Mapper\Dbal\Conventions\IConventions;
use Nextras\Orm\Mapper\Dbal\DbalMapper;
use Override;

/**
 * @template E of IEntity
 *
 * @extends DbalMapper<E>
 */
class BaseMapper extends DbalMapper {
	#[Override]
	protected function createConventions(): IConventions {
		$conventions = parent::createConventions();
		\assert($conventions instanceof Conventions); // property is not available on interface
		$conventions->manyHasManyStorageNamePattern = '%s_%s';

		return $conventions;
	}
}
