<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Token
 * @property int $id {primary}
 * @property User $user {m:1 User::$tokens}
 * @property string $token
 * @property string $type {default self::REGISTRATION} {enum self::REGISTRATION, self::LOGIN, self::PASSWORD}
 * @property DateTime $timestamp {default now}
 * @property DateTime $expiration
 * @property string $ip
 */
class Token extends Entity {
	const REGISTRATION = 'registration';
	const LOGIN = 'login';
	const PASSWORD = 'password';
}
