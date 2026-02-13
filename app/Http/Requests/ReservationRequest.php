<?php

namespace App\Http\Requests;

use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class ReservationRequest extends Data
{
    public function __construct(
        #[Uuid]
        public string $property_id,

        #[Date]
        public string $check_in,

        #[Date, After('check_in')]
        public ?string $check_out = null,

        #[In(['confirmed', 'pending', 'cancelled'])]
        public ?string $status = null,
    ) {}
}
