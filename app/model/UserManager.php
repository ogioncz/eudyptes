<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;
use Nette\Security\Passwords;
use App;

class UserManager extends Nette\Object implements Nette\Security\IAuthenticator {
	/** @var App\Model\UserRepository */
	private $users;


	public function __construct(App\Model\UserRepository $users) {
		$this->users = $users;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		list($username, $password) = $credentials;

		$user = $this->users->getBy(['username' => $username]);

		if (!$user) {
			throw new Nette\Security\AuthenticationException('Zadal jsi neexistující uživatelské jméno.', self::IDENTITY_NOT_FOUND);
		} else if (!Passwords::verify($password, $user->password)) {
			throw new Nette\Security\AuthenticationException('Zadal jsi nesprávné heslo.', self::INVALID_CREDENTIAL);
		} else if (Passwords::needsRehash($user->password)) {
			$user->password = Passwords::hash($password);
			$this->users->persistAndFlush($user);
		}

		return new Nette\Security\Identity($user->id, $user->role, ['username' => $user->username, 'registered' => $user->registered]);
	}
}
