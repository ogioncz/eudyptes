<?php

namespace App\Model;

use DateTimeImmutable;
use Nette;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Mail
 * @property int $id {primary}
 * @property User|null $sender {m:1 User::$sentMail}
 * @property User|null $recipient {m:1 User::$receivedMail}
 * @property Mail|null $reaction {m:1 Mail::$replies}
 * @property string $subject
 * @property string $markdown
 * @property string $content
 * @property DateTimeImmutable $timestamp {default now}
 * @property string $ip
 * @property bool $read {default false}
 *
 * @property Mail $root {virtual}
 * @property OneHasMany|Mail[] $replies {1:m Mail::$reaction, orderBy=[timestamp, ASC]}
 */
class Mail extends Entity implements Nette\Security\IResource {
	public function getResourceId() {
		return 'mail';
	}

	public function getterRoot() {
		$mail = $this;
		while ($mail->reaction) {
			$mail = $mail->reaction;
		}
		return $mail;
	}
}
