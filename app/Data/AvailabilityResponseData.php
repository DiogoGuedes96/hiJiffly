<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AvailabilityResponseData extends Data
{
    public function __construct(
        public string $property_id,
        public string $check_in,
        public string $check_out,
        public int $nights,
        public int $adults,
        public string $currency,
        /** @var RoomAvailabilityData[] */
        public array $rooms,
    ) {}
}
