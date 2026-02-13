<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AuthResponseData extends Data
{
    public function __construct(
        public string $token,
        public string $token_type,
        public UserData $user,
    ) {}
}
