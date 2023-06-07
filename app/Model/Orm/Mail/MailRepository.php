<?php

declare(strict_types=1);

namespace App\Model\Orm\Mail;

use Nextras\Orm\Repository\Repository;

class MailRepository extends Repository {
	public static function getEntityClassNames(): array {
		return [Mail::class];
	}
}
