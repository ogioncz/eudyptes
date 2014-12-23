<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Shout
 * @property User $user {m:1 UserRepository $tokens}
 * @property string $token
 * @property string $type {default registration} {enum self::REGISTRATION self::LOGIN self::PASSWORD}
 * @property DateTime $timestamp {default now}
 * @property DateTime $expiration
 * @property string $ip
 */
class Token extends Entity {
	const REGISTRATION = 'registration';
	const LOGIN = 'login';
	const PASSWORD = 'password';
}
