<?php

namespace App\Model;

use Nette;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * Stamp
 * @property int $id {primary}
 * @property string $name
 * @property string $description
 * @property string $difficulty {enum self::DIFFICULTY_*}
 * @property string $icon
 * @property bool $obtainable
 *
 * @property ManyHasMany|User[] $owners {m:m User::$ownedStamps}
 */
class Stamp extends Entity implements Nette\Security\IResource {
	const DIFFICULTY_NOTHING = '0';
	const DIFFICULTY_EASY = '1';
	const DIFFICULTY_MODERATE = '2';
	const DIFFICULTY_MEDIUM = '3';
	const DIFFICULTY_HARD = '4';
	const DIFFICULTY_CRAZY = '5';

	public function getResourceId() {
		return 'stamp';
	}
}
