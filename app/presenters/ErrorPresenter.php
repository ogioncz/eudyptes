<?php

declare(strict_types=1);

namespace App\Presenters;

use Exception;
use Nette;
use Tracy\Debugger;

/**
 * ErrorPresenter handles errors.
 */
class ErrorPresenter extends BasePresenter {
	public function renderDefault(Exception $exception): void {
		if ($exception instanceof Nette\Application\BadRequestException) {
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(\in_array($code, [403, 404, 405, 410, 500], true) ? $code : '4xx');
			// log to access.log
			Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
		} else {
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}

		if ($this->isAjax()) { // AJAX request? Note this error in payload.
			$this->getPayload()->error = true;
			$this->terminate();
		}
	}
}
