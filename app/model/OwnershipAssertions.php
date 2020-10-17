<?php

namespace App\Model;

use Nette;
use Nette\Security\Permission;
use Nette\Security\IRole;
use Nette\Security\IResource;

class OwnershipAssertions {
	use Nette\SmartObject;

	public static function ownsPage(Permission $acl) {
		return $acl->getQueriedResource() instanceof IResource && $acl->getQueriedRole() instanceof IRole && $acl->getQueriedRole()->id === $acl->getQueriedResource()->user->id;
	}

	public static function ownsMail(Permission $acl) {
		return $acl->getQueriedResource() instanceof IResource && $acl->getQueriedRole() instanceof IRole && ($acl->getQueriedRole()->id === $acl->getQueriedResource()->sender->id || $acl->getQueriedRole()->id === $acl->getQueriedResource()->recipient->id);
	}

	public static function ownsProfile(Permission $acl) {
		return $acl->getQueriedResource() instanceof IResource && $acl->getQueriedRole() instanceof IRole && $acl->getQueriedRole()->id === $acl->getQueriedResource()->id;
	}

	public static function canMail(Permission $acl) {
		return $acl->getQueriedResource() instanceof IResource && $acl->getQueriedRole() instanceof IRole && $acl->getQueriedRole()->id !== $acl->getQueriedResource()->id;
	}
}
