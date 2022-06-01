<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Security\Permission;
use Nette\Security\Resource;
use Nette\Security\Role;
use Nette\SmartObject;

class OwnershipAssertions {
	use SmartObject;

	public static function ownsPage(Permission $acl): bool {
		return $acl->getQueriedResource() instanceof Resource && $acl->getQueriedRole() instanceof Role && $acl->getQueriedRole()->id === $acl->getQueriedResource()->user->id;
	}

	public static function ownsMail(Permission $acl): bool {
		return $acl->getQueriedResource() instanceof Resource && $acl->getQueriedRole() instanceof Role && ($acl->getQueriedRole()->id === $acl->getQueriedResource()->sender->id || $acl->getQueriedRole()->id === $acl->getQueriedResource()->recipient->id);
	}

	public static function ownsProfile(Permission $acl): bool {
		return $acl->getQueriedResource() instanceof Resource && $acl->getQueriedRole() instanceof Role && $acl->getQueriedRole()->id === $acl->getQueriedResource()->id;
	}

	public static function canMail(Permission $acl): bool {
		return $acl->getQueriedResource() instanceof Resource && $acl->getQueriedRole() instanceof Role && $acl->getQueriedRole()->id !== $acl->getQueriedResource()->id;
	}
}
