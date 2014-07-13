<?php

namespace App\Presenters;

use Nette, App\Model;


/**
 * Mail presenter.
 */
class MailPresenter extends BasePresenter {
	/** @var Nette\Database\Context @inject */
	public $database;

	private $items_per_page = 25;

	public function renderList($sent = false) {
		$this->template->sent = $sent;
		$paginator = $this["paginator"]->getPaginator();
		$paginator->itemsPerPage = $this->items_per_page;
		$paginator->itemCount = $this->database->table('mail')->count('*');
		$this->template->mails = $this->database->table('mail')->where($sent ? 'from' : 'to', $this->user->identity->id)->order('timestamp DESC')->limit($paginator->itemsPerPage, $paginator->offset);
	}

	public function renderShow($id) {
		$mail = $this->database->table('mail')->get($id);

		if(!$mail) {
			$this->error('Tato zpráva neexistuje');
		}

		if($mail->ref('user', 'from')->id !== $this->user->identity->id && $mail->ref('user', 'to')->id !== $this->user->identity->id) {
			$this->error('Toto není tvá zpráva', Nette/Http/IResponse::S403_FORBIDDEN);
		}

		$this->template->mail = $mail;
	}
}
