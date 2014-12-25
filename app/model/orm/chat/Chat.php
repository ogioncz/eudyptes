<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Chat
 * @property User $user {m:1 UserRepository $createdChats}
 * @property string $content
 * @property DateTime $timestamp {default now}
 * @property string $ip
 * @property int $board {default 0}
 */
class Chat extends Entity {
}
