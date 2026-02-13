<?php

namespace App\Clients;

use App\Exceptions\MewsApiException;

class MewsServicesEndpoint extends MewsClient
{
    private const ENDPOINT = '/api/connector/v1/services/getAll';

    /**
     * Get all services from Mews API
     *
     * @param string $propertyId Property/Enterprise ID to filter services
     * @return array
     * @throws MewsApiException
     */
    public function getAll(string $propertyId): array
    {
        $response = $this->post(self::ENDPOINT);
        return $this->filterServices($response->json(), $propertyId);
    }

    /**
     * Filter bookable Day-based services from services data
     *
     * @param array $servicesData
     * @param string $propertyId
     * @return array
     * @throws MewsApiException
     */
    private function filterServices(array $servicesData, string $propertyId): array
    {
        $bookableServices = [];
        
        if (isset($servicesData['Services'])) {
            foreach ($servicesData['Services'] as $service) {
                if (isset($service['Data']['Discriminator']) && 
                    $service['Data']['Discriminator'] === 'Bookable' &&
                    ($service['Id'] === $propertyId || $service['EnterpriseId'] === $propertyId) &&
                    $service['IsActive']  === true
                    ) {
                    $timeUnitPeriod = $service['Data']['Value']['TimeUnitPeriod'] ?? null;
                    
                    if ($timeUnitPeriod === 'Day') {
                        $bookableServices[] = $service;
                    }
                }
            }
        }

        if (empty($bookableServices)) {
            throw new MewsApiException('No bookable services found', 404);
        }
        
        return $bookableServices;
    }
}