<?php

/**
 * This file is based on the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/forms
 * @author     Jan Tvrdik (http://merxes.cz)
 */

namespace App\Components;

use DateTimeImmutable;

class TimePicker extends \Nextras\Forms\Controls\DateTimePickerPrototype {
	/** @link http://www.w3.org/html/wg/drafts/html/master/infrastructure.html#valid-time-string */
	const W3C_TIME_FORMAT = 'H:i';

	/** @var string */
	protected $htmlFormat = self::W3C_TIME_FORMAT;

	/** @var string */
	protected $htmlType = 'time';


	protected function getDefaultParser() {
		return function($value) {
			if (!preg_match('#^(?P<HH>0?[0-9]|1[0-9]|2[0-3]):(?P<mm>[0-5][0-9])$#', $value, $matches)) {
				return null;
			}

			$HH = $matches['HH'];
			$mm = $matches['mm'];

			$value = new DateTimeImmutable;
			return $value->setTime($HH, $mm);
		};
	}

}
