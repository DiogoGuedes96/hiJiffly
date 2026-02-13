<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ReservationResponseData extends Data
{
    public function __construct(
        public string $property_id,
        public string $check_in,
        public ?string $check_out,
        public ?string $status,
        
        #[DataCollectionOf(ReservationItemData::class)]
        public DataCollection $reservations,
    ) {}
}
