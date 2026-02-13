<?php

namespace App\Clients;

use App\Exceptions\MewsApiException;

class MewsAvailabilityEndpoint extends MewsClient
{
    private const ENDPOINT = '/api/connector/v1/services/getAvailability';

    /**
     * Get availability for a specific service
     *
     * @param string $serviceId
     * @param string $firstTimeUnitStartUtc
     * @param string $lastTimeUnitStartUtc
     * @return array
     * @throws MewsApiException
     */
    public function getForService(
        string $serviceId,
        string $firstTimeUnitStartUtc,
        string $lastTimeUnitStartUtc,
        array $resourceCategories,
        int $adults
    ): array {
        $response = $this->post(self::ENDPOINT, [
            'ServiceId' => $serviceId,
            'FirstTimeUnitStartUtc' => $firstTimeUnitStartUtc,
            'LastTimeUnitStartUtc' => $lastTimeUnitStartUtc,
        ]);

        // Process availability and filter by capacity
        return $this->processAvailability(
                $response->json(),
                $resourceCategories,
                $adults
            );
    }

    /**
     * Process availability data and filter by adult capacity
     *
     * @param array $availabilityData
     * @param array $resourceCategories
     * @param int $adults
     * @return array
     */
    public function processAvailability(
        array $availabilityData,
        array $resourceCategories,
        int $adults
    ): array {
        $availableCategories = [];
        $categoryAvailabilities = $availabilityData['CategoryAvailabilities'] ?? [];

        foreach ($categoryAvailabilities as $categoryAvailability) {
            $categoryId = $categoryAvailability['CategoryId'];
            $availabilities = $categoryAvailability['Availabilities'] ?? [];
            $adjustments = $categoryAvailability['Adjustments'] ?? [];

            // Calculate effective availability for each day
            $isAvailable = true;
            for ($i = 0; $i < count($availabilities); $i++) {
                $effectiveAvailability = $availabilities[$i] + ($adjustments[$i] ?? 0);
                
                // If any day has 0 or negative availability, room type is not available
                if ($effectiveAvailability <= 0) {
                    $isAvailable = false;
                    break;
                }
            }

            if (!$isAvailable) {
                continue;
            }

            // Find matching resource category
            $category = $this->findResourceCategory($resourceCategories, $categoryId);
            
            if (!$category) {
                continue;
            }

            // Filter by adult capacity
            $capacity = $category['Capacity'] ?? 0;
            if ($capacity < $adults) {
                continue;
            }

            // Handle multilingual names: prefer en-US, fallback to Name, then Unknown
            $name = 'Unknown';
            if (isset($category['Names']['en-US'])) {
                $name = $category['Names']['en-US'];
            } elseif (isset($category['Name'])) {
                $name = $category['Name'];
            } elseif (isset($category['Names']) && is_array($category['Names']) && count($category['Names']) > 0) {
                // Fallback to first available language
                $name = reset($category['Names']);
            }
            
            $availableCategories[] = [
                'categoryId' => $categoryId,
                'name' => $name,
                'capacity' => $capacity,
                'availabilities' => $availabilities,
                'adjustments' => $adjustments,
            ];
        }

        return $availableCategories;
    }

    /**
     * Find resource category by ID
     *
     * @param array $categories
     * @param string $categoryId
     * @return array|null
     */
    private function findResourceCategory(array $categories, string $categoryId): ?array
    {
        foreach ($categories as $category) {
            if ($category['Id'] === $categoryId) {
                return $category;
            }
        }
        
        return null;
    }
}
