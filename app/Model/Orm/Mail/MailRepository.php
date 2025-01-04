<?php

declare(strict_types=1);

namespace App\Model\Orm\Mail;

use Nextras\Orm\Repository\Repository;
use Override;

/**
 * @extends Repository<Mail>
 */
class MailRepository extends Repository {
	#[Override]
	public static function getEntityClassNames(): array {
		return [Mail::class];
	}
}
