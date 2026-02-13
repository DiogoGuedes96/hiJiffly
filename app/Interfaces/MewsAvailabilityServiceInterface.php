<?php

namespace App\Interfaces;

use App\Http\Requests\AvailabilityRequest;
use App\Data\AvailabilityResponseData;
use App\Exceptions\MewsApiException;

interface MewsAvailabilityServiceInterface
{
    /**
     * Get available rooms for the given parameters
     *
     * @param AvailabilityRequest $data
     * @return AvailabilityResponseData
     * @throws MewsApiException
     */
    public function getAvailability(AvailabilityRequest $data): AvailabilityResponseData;
}
