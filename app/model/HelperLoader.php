<?php

namespace App\Model;

use Nette;
use Nette\Utils\Html;

class HelperLoader extends Nette\Object {
	private $presenter;

	public function __construct($presenter) {
		$this->presenter = $presenter;
	}

	public function loader($args) {
		$args = func_get_args();
		$func = $args[0];
		unset($args[0]);

		if (method_exists($this, $func)) {
			return call_user_func_array(array($this, $func), $args);
		} else {
			return null;
		}
	}

	public function userLink($user) {
		return Html::el('a', $user->username)->href($this->presenter->link('profile:show', $user->id));
	}

	public function relDate($date) {
		if($date == (new \DateTime('today'))) {
			return '(dnes)';
		} else if($date == (new \DateTime('tomorrow'))) {
			return '(zítra)';
		}
		return '';
	}

	public function dateNA($time, $format = null) {
		if($time) {
			return \Latte\Runtime\Filters::date($time, $format);
		} else {
			return 'N/A';
		}
	}
}
