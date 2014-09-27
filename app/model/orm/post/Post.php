<?php

namespace App\Model;

use Nette\Utils\DateTime;
use Nextras\Orm\Entity\Entity;


/**
 * Post
 * @property User $user {m:1 UserRepository $createdPosts}
 * @property string $title
 * @property string $markdown
 * @property string $content
 * @property DateTime $timestamp {default now}
 * @property bool $likeable {default false}
 */
class Post extends Entity {
}
