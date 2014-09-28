<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Mail
 * @property User|null $sender {m:1 UserRepository $sentMail}
 * @property User|null $recipient {m:1 UserRepository $receivedMail}
 * @property Mail|null $reaction {m:1 MailRepository $replies}
 * @property string $subject
 * @property string $markdown
 * @property string $content
 * @property DateTime $timestamp {default now}
 * @property string $ip
 * @property bool $read {default false}
 *
 * @property OneHasMany|Mail[] $replies {1:m MailRepository $reaction order:timestamp,ASC}
 */
class Mail extends Entity {
}
