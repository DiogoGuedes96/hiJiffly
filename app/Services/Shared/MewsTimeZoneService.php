<?php

namespace App\Services\Shared;

class MewsTimeZoneService 
{
    /**
     * Get the Mews timezone from configuration
     * 
     * @return string
     */
    public function getMewsTimeZone(): string
    {
        //TODO Also explained in .env
        //Timezone setted by default to budapest because when calling mews configuration endpoint, the "TimeZoneIdentifier" is set to "Europe/Budapest"
        //Ideally in some kind of boot process before calling the availability endpoint, we should call the configuration endpoint and get the timezone from there,
        //but for now, to avoid multiple calls to the api and for time reasons, I will set it hardcoded as a project variable
        return config('mews.timezone_override') ?? 'UTC';
    }

    /**
     * Convert dates to UTC for Mews Availability API (Day-based services)
     * For Day-based services: TimeUnit boundary = local midnight
     * Check-out is exclusive, so we subtract 1 day
     *
     * @param string $checkIn YYYY-MM-DD
     * @param string $checkOut YYYY-MM-DD (exclusive)
     * @param string|null $timezone Optional timezone, defaults to configured Mews timezone
     * @return array ['firstTimeUnitStartUtc' => string, 'lastTimeUnitStartUtc' => string]
     */
    public function convertDatesToUtcForAvailability(string $checkIn, string $checkOut, ?string $timezone = null): array
    {
        $tz = new \DateTimeZone($timezone ?? $this->getMewsTimeZone());
        
        // Check-in at midnight
        $checkInDate = new \DateTime($checkIn, $tz);
        $checkInDate->setTime(0, 0, 0, 0);
        
        // Check-out is exclusive, so we need (check_out - 1 day) at midnight
        $checkOutDate = new \DateTime($checkOut, $tz);
        $checkOutDate->setTime(0, 0, 0, 0);
        $checkOutDate->modify('-1 day');
        
        // Convert to UTC
        $utcTz = new \DateTimeZone('UTC');
        $checkInDate->setTimezone($utcTz);
        $checkOutDate->setTimezone($utcTz);
        
        return [
            'firstTimeUnitStartUtc' => $checkInDate->format('Y-m-d\TH:i:s.000\Z'),
            'lastTimeUnitStartUtc' => $checkOutDate->format('Y-m-d\TH:i:s.000\Z'),
        ];
    }

    /**
     * Convert dates to UTC for Mews Reservations API
     * Start date at midnight, end date at midnight (or +1 year if not provided)
     *
     * @param string $checkIn YYYY-MM-DD
     * @param string|null $checkOut YYYY-MM-DD (optional)
     * @param string|null $timezone Optional timezone, defaults to configured Mews timezone
     * @return array ['startUtc' => string, 'endUtc' => string]
     */
    public function convertDatesToUtcForReservations(string $checkIn, ?string $checkOut = null, ?string $timezone = null): array
    {
        $tz = new \DateTimeZone($timezone ?? $this->getMewsTimeZone());
        
        // Start date at midnight
        $startDate = new \DateTime($checkIn, $tz);
        $startDate->setTime(0, 0, 0, 0);
        
        // End date at midnight (or default to check-in + 1 year if not provided)
        if ($checkOut) {
            $endDate = new \DateTime($checkOut, $tz);
        } else {
            $endDate = clone $startDate;
            $endDate->modify('+1 year');
        }
        $endDate->setTime(0, 0, 0, 0);
        
        // Convert to UTC
        $utcTz = new \DateTimeZone('UTC');
        $startDate->setTimezone($utcTz);
        $endDate->setTimezone($utcTz);
        
        return [
            'startUtc' => $startDate->format('Y-m-d\TH:i:s\Z'),
            'endUtc' => $endDate->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}