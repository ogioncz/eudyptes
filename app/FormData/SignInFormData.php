<?php

declare(strict_types=1);

namespace App\FormData;

final readonly class SignInFormData {
	public function __construct(
		public bool $remember,
		public string $username,
		public string $password,
	) {
	}
}
