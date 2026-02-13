<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RoomAvailabilityData extends Data
{
    public function __construct(
        public string $room_description,
        public float $price,
        public string $currency,
    ) {}
}
