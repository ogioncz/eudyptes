<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Orm\User\UserRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class UserManager implements Authenticator {
	public function __construct(
		private readonly UserRepository $users,
		private readonly Passwords $passwords,
	) {
	}

	/**
	 * Performs an authentication.
	 *
	 * @throws AuthenticationException
	 *
	 * @return SimpleIdentity
	 */
	public function authenticate(string $username, string $password): IIdentity {
		$user = $this->users->getBy(['username' => $username]);

		if (!$user) {
			throw new AuthenticationException('Zadal jsi neexistující uživatelské jméno.', self::IdentityNotFound);
		} elseif (!$this->passwords->verify($password, $user->password)) {
			throw new AuthenticationException('Zadal jsi nesprávné heslo.', self::InvalidCredential);
		} elseif ($this->passwords->needsRehash($user->password)) {
			$user->password = $this->passwords->hash($password);
			$this->users->persistAndFlush($user);
		}

		return new SimpleIdentity($user->id, $user->role, ['username' => $user->username, 'registered' => $user->registered]);
	}
}
