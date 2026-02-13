<?php

namespace App\Interfaces;

use App\Data\ReservationResponseData;
use App\Http\Requests\ReservationRequest;

interface ReservationServiceInterface
{
    /**
     * Get available rooms for the given parameters
     *
     * @param ReservationRequest $data
     * @return ReservationResponseData
     * @throws MewsApiException
     */
    public function getReservations(ReservationRequest $data): ReservationResponseData;
}
