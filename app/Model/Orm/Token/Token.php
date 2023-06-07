<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * Token.
 *
 * @property int $id {primary}
 * @property User $user {m:1 User::$tokens}
 * @property string $token
 * @property string $type {default self::REGISTRATION} {enum self::REGISTRATION, self::LOGIN, self::PASSWORD}
 * @property DateTimeImmutable $timestamp {default now}
 * @property DateTimeImmutable $expiration
 * @property string $ip
 */
class Token extends Entity {
	public const REGISTRATION = 'registration';
	public const LOGIN = 'login';
	public const PASSWORD = 'password';
}
