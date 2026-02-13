<?php

namespace App\Clients;

use App\Exceptions\MewsApiException;

class MewsReservationsEndpoint extends MewsClient
{
    private const ENDPOINT = '/api/connector/v1/reservations/getAll';

    /**
     * Get all reservations for given parameters
     *
     * @param string $enterpriseId Property/Enterprise ID
     * @param array $states Reservation states (e.g., ['Confirmed'])
     * @param string $startUtc Start date in UTC format
     * @param string $endUtc End date in UTC format
     * @return array
     * @throws MewsApiException
     */
    public function getAll(
        string $enterpriseId,
        array $states,
        string $startUtc,
        string $endUtc
    ): array {
        $response = $this->post(self::ENDPOINT, [
            'EnterpriseIds' => [$enterpriseId],
            'States' => $states,
            'StartUtc' => $startUtc,
            'EndUtc' => $endUtc,
        ]);

        return $response->json();
    }
}
