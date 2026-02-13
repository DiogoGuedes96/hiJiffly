<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ReservationItemData extends Data
{
    public function __construct(
        public string $reservation_id,
        public string $status,
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $phone_number,
        public string $booking_channel,
        public string $room_state,
        public ?string $room_number,
        public string $room_type,
        public string $room_category,
        public string $check_in,
        public string $check_out,
    ) {}
}
