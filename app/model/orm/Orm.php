<?php

namespace App\Model;

use Nextras\Orm\Model\DIModel;

/**
 * Model
 * @property-read MeetingRepository $meetings
 * @property-read UserRepository $users
 * @property-read PostRepository $posts
 * @property-read MailRepository $mails
 * @property-read PageRepository $pages
 * @property-read RevisionRepository $revisions
 */
class Orm extends DIModel {
}
