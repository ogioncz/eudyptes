<?php

declare(strict_types=1);

namespace App\Model\Orm;

use App\Model\Orm\Chat\ChatRepository;
use App\Model\Orm\Mail\MailRepository;
use App\Model\Orm\Meeting\MeetingRepository;
use App\Model\Orm\Page\PageRepository;
use App\Model\Orm\Post\PostRepository;
use App\Model\Orm\PostRevision\PostRevisionRepository;
use App\Model\Orm\Revision\RevisionRepository;
use App\Model\Orm\Stamp\StampRepository;
use App\Model\Orm\Token\TokenRepository;
use App\Model\Orm\User\UserRepository;
use Nextras\Orm\Model\Model;

/**
 * Model.
 *
 * @property-read MeetingRepository $meetings
 * @property-read UserRepository $users
 * @property-read PostRepository $posts
 * @property-read MailRepository $mails
 * @property-read ChatRepository $chats
 * @property-read PageRepository $pages
 * @property-read TokenRepository $tokens
 * @property-read RevisionRepository $revisions
 * @property-read PostRevisionRepository $postRevisions
 * @property-read StampRepository $stamps
 */
class Orm extends Model {
}
