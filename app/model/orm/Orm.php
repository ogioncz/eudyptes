<?php

namespace App\Model;

use Nextras\Orm\Model\Model;

/**
 * Model
 * @property-read MeetingRepository $meetings
 * @property-read UserRepository $users
 * @property-read PostRepository $posts
 * @property-read MailRepository $mails
 * @property-read ChatRepository $chats
 * @property-read PageRepository $pages
 * @property-read TokenRepository $tokens
 * @property-read RevisionRepository $revisions
 * @property-read StampRepository $stamps
 */
class Orm extends Model {
}
