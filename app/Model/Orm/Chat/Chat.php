<?php

declare(strict_types=1);

namespace App\Model\Orm\Chat;

use App\Model\Orm\User\User;
use DateTimeImmutable;
use Nextras\Orm\Entity\Entity;

/**
 * Chat.
 *
 * @property int $id {primary}
 * @property User $user {m:1 User::$createdChats}
 * @property string $content
 * @property DateTimeImmutable $timestamp {default now}
 * @property string $ip
 * @property int $board {default 0}
 */
class Chat extends Entity {
}
