<?php

namespace App\Presenters;

use Nette;
use App;

class ProfilePresenter extends BasePresenter {
	/** @var Nette\Database\Context @inject */
	public $database;

	/** @var App\Model\UserRepository @inject */
	public $users;

	public function renderList() {
		$this->template->profiles = $this->users->findAll()->orderBy('username');
	}

	public function renderShow($id) {
		$profile = $this->users->getById($id);
		if(!$profile) {
			$this->error('Uživatel nenalezen');
		}
		if(file_exists($this->context->parameters['avatarStorage'] . '/' . $profile->id . 'm.png')) {
			$this->template->avatar = str_replace('♥basePath♥', $this->context->httpRequest->url->baseUrl, $this->context->parameters['avatarStoragePublic']) . '/' . $profile->id . 'm.png';
		}

		$this->template->isMe = $this->user->loggedIn && $this->user->identity->id === $profile->id;
		$this->template->ipAddress = $this->context->httpRequest->remoteAddress;
		$this->template->profile = $profile;
	}
}
