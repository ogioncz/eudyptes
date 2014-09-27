<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;
use Nette\Security\Passwords;

class UserManager extends Nette\Object implements Nette\Security\IAuthenticator {
	const TABLE_NAME = 'user';
	const COLUMN_ID = 'id';
	const COLUMN_NAME = 'username';
	const COLUMN_PASSWORD_HASH = 'password';
	const COLUMN_ROLE = 'role';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('Zadal jsi neexistující uživatelské jméno.', self::IDENTITY_NOT_FOUND);
		} else if (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('Zadal jsi nesprávné heslo.', self::INVALID_CREDENTIAL);
		} else if (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password) {
		$this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
		));
	}
}
