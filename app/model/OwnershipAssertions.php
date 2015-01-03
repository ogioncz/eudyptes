<?php

namespace App\Model;

use Nette;
use Nette\Security\Permission;
use Nette\Security\IRole;
use Nette\Security\IResource;

class OwnershipAssertions extends Nette\Object {
	public static function ownsPage(Permission $acl) {
		return $acl->queriedResource instanceof IResource && $acl->queriedRole instanceof IRole && $acl->queriedRole->id === $acl->queriedResource->user->id;
	}

	public static function ownsMail(Permission $acl) {
		return $acl->queriedResource instanceof IResource && $acl->queriedRole instanceof IRole && ($acl->queriedRole->id === $acl->queriedResource->sender->id || $acl->queriedRole->id === $acl->queriedResource->recipient->id);
	}
}
