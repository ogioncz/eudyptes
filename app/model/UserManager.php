<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;

class UserManager implements Nette\Security\IAuthenticator {
	use Nette\SmartObject;

	public function __construct(
		private UserRepository $users,
		private Passwords $passwords,
	) {
	}

	/**
	 * Performs an authentication.
	 *
	 * @throws Nette\Security\AuthenticationException
	 *
	 * @return Nette\Security\SimpleIdentity
	 */
	public function authenticate(array $credentials): Nette\Security\IIdentity {
		[$username, $password] = $credentials;

		$user = $this->users->getBy(['username' => $username]);

		if (!$user) {
			throw new Nette\Security\AuthenticationException('Zadal jsi neexistující uživatelské jméno.', self::IDENTITY_NOT_FOUND);
		} elseif (!$this->passwords->verify($password, $user->password)) {
			throw new Nette\Security\AuthenticationException('Zadal jsi nesprávné heslo.', self::INVALID_CREDENTIAL);
		} elseif ($this->passwords->needsRehash($user->password)) {
			$user->password = $this->passwords->hash($password);
			$this->users->persistAndFlush($user);
		}

		return new Nette\Security\SimpleIdentity($user->id, $user->role, ['username' => $user->username, 'registered' => $user->registered]);
	}
}
