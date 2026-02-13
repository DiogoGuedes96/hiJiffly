<?php

namespace App\Http\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class RegisterRequest extends Data
{
    public function __construct(
        #[Required, Min(2)]
        public string $name,

        #[Email, Required]
        public string $email,

        #[Required, Min(6)]
        public string $password,
    ) {}
}
