<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Orm\User\UserRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\SmartObject;

class UserManager implements IAuthenticator {
	use SmartObject;

	public function __construct(
		private UserRepository $users,
		private Passwords $passwords,
	) {
	}

	/**
	 * Performs an authentication.
	 *
	 * @throws AuthenticationException
	 *
	 * @return SimpleIdentity
	 */
	public function authenticate(array $credentials): IIdentity {
		[$username, $password] = $credentials;

		$user = $this->users->getBy(['username' => $username]);

		if (!$user) {
			throw new AuthenticationException('Zadal jsi neexistující uživatelské jméno.', self::IDENTITY_NOT_FOUND);
		} elseif (!$this->passwords->verify($password, $user->password)) {
			throw new AuthenticationException('Zadal jsi nesprávné heslo.', self::INVALID_CREDENTIAL);
		} elseif ($this->passwords->needsRehash($user->password)) {
			$user->password = $this->passwords->hash($password);
			$this->users->persistAndFlush($user);
		}

		return new SimpleIdentity($user->id, $user->role, ['username' => $user->username, 'registered' => $user->registered]);
	}
}
