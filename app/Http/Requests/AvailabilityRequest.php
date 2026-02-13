<?php

namespace App\Http\Requests;

use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class AvailabilityRequest extends Data
{
    public function __construct(
        #[Uuid]
        public string $property_id,

        #[Date, AfterOrEqual('today')]
        public string $check_in,

        #[Date, After('check_in')]
        public string $check_out,

        #[Min(1)]
        public int $adults,
    ) {}
}
