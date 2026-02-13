<?php

namespace App\Clients;

use App\Exceptions\MewsApiException;

class MewsResourceCategoriesEndpoint extends MewsClient
{
    private const ENDPOINT = '/api/connector/v1/resourceCategories/getAll';

    /**
     * Get resource categories for specified services
     *
     * @param array $serviceIds
     * @return array
     * @throws MewsApiException
     */
    public function getByServiceIds(array $serviceIds): array
    {
        $response = $this->post(self::ENDPOINT, [
            'ServiceIds' => $serviceIds,
        ]);

        return $response->json()['ResourceCategories'] ?? [];
    }

    /**
     * Get resource categories in batches (Mews API limit is 1000 service IDs per request)
     *
     * @param array $bookableServices
     * @param int $batchSize
     * @return array
     */
    public function getBatched(array $bookableServices, int $batchSize = 1000): array
    {
        $allCategories = [];
        $serviceIds = array_map(fn($service) => $service['Id'], $bookableServices);
        $batches = array_chunk($serviceIds, $batchSize);
        
        foreach ($batches as $batch) {
            $categories = $this->getByServiceIds($batch);
            $allCategories = array_merge($allCategories, $categories);
        }
        
        return $allCategories;
    }
}
