<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ReservationData extends Data
{
    public function __construct(
        public int $id,
        public int $mew_id,
        public string $customer_name,
        public string $customer_email,
        public string $start_date,
        public string $end_date,
        public string $status,
        public int $number_of_guests,
        public ?MewData $mew = null,
    ) {}
}
