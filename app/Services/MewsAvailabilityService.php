<?php

namespace App\Services;

use App\Clients\MewsAvailabilityEndpoint;
use App\Clients\MewsResourceCategoriesEndpoint;
use App\Clients\MewsServicesEndpoint;
use App\Http\Requests\AvailabilityRequest;
use App\Data\AvailabilityResponseData;
use App\Data\RoomAvailabilityData;
use App\Exceptions\MewsApiException;
use App\Interfaces\MewsAvailabilityServiceInterface;
use App\Services\Shared\MewsTimeZoneService;
use Carbon\Carbon;

class MewsAvailabilityService implements MewsAvailabilityServiceInterface
{
    public function __construct(
        private readonly MewsServicesEndpoint $servicesEndpoint,
        private readonly MewsResourceCategoriesEndpoint $resourceCategoriesEndpoint,
        private readonly MewsAvailabilityEndpoint $availabilityEndpoint,
        private readonly MewsTimeZoneService $mewsTimeZoneService,
    ) {}

    /**
     * Get available rooms for the given parameters
     *
     * @param AvailabilityRequest $data
     * @return AvailabilityResponseData
     * @throws MewsApiException
     */
    public function getAvailability(AvailabilityRequest $data): AvailabilityResponseData
    {
        $checkIn = Carbon::parse($data->check_in);
        $checkOut = Carbon::parse($data->check_out);

        // Calculate nights (check-in to check-out)
        $nights = $checkIn->diffInDays($checkOut);

        //Get all bookable services
        $bookableServices = $this->servicesEndpoint->getAll($data->property_id);
        
        //Get resource categories for all services
        $allResourceCategories = $this->resourceCategoriesEndpoint->getBatched($bookableServices);
        
        // Process each service and get availability
        $rooms = $this->processServicesAvailability(
            $bookableServices,
            $allResourceCategories,
            $data->check_in,
            $data->check_out,
            $data->adults,
            $nights
            );

        // TODO: Note -> I was not able to figure it out how to get currency from availability in Mews, so pricing will be set to EUR on purpose.
        // Same as Pricing
        $currency = 'EUR';

        return AvailabilityResponseData::from([
            'property_id' => $data->property_id,
            'check_in' => $data->check_in,
            'check_out' => $data->check_out,
            'nights' => $nights,
            'adults' => $data->adults,
            'currency' => $currency,
            'rooms' => $rooms,
        ]);
    }

    /**
     * Process availability for all services
     *
     * @param array $services
     * @param array $resourceCategories
     * @param string $checkIn
     * @param string $checkOut
     * @param int $adults
     * @param int $nights
     * @return array
     */
    private function processServicesAvailability(
        array $services,
        array $resourceCategories,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $nights
    ): array {
        $uniqueRooms = []; // Use categoryId as key to deduplicate

        foreach ($services as $service) {
            $serviceId = $service['Id'];

            // Convert dates to UTC timestamps
            $utcTimes = $this->mewsTimeZoneService->convertDatesToUtcForAvailability(
                $checkIn,
                $checkOut
            );

            // Get availability for this service
            $availableCategories = $this->availabilityEndpoint->getForService(
                $serviceId,
                $utcTimes['firstTimeUnitStartUtc'],
                $utcTimes['lastTimeUnitStartUtc'],
                $resourceCategories,
                $adults
            );

            $uniqueRooms = array_merge($uniqueRooms, $this->buildRoomsArray($availableCategories, $nights));
        }

        return array_values($uniqueRooms);
    }

    /**
     * Build rooms array from available categories, deduplicating by categoryId
     *
     * @param array $availableCategories
     * @param int $nights
     * @return array
     */
    private function buildRoomsArray(array $availableCategories, int $nights): array {
        $uniqueRooms = [];
        // Add to unique rooms collection
        foreach ($availableCategories as $roomType) {
            $categoryId = $roomType['categoryId'] ?? null;
            $roomDescription = $roomType['name'] ?? 'Unknown Room';
            
            if (!$categoryId) {
                continue; // Skip if no categoryId
            }
            
            // Skip if we already have this room type (deduplicate by categoryId)
            if (isset($uniqueRooms[$categoryId])) {
                continue;
            }
            
            // TODO: Note -> I was not able to figure it out how to get pricing from availability in Mews, so pricing will be set to 0 on purpose.
            // Same as currency (I imagine that pricing and currency should beavailable in a separte endpoint, where i need to cross check data with Categories...)
            // I imagine that, for example if a service has 2 categories the 2 categories PROBABLY will have diferent pricing acordingly with data ranges... So i need to match that with my availabilities  
            $pricePerNight = 0;
            $totalPrice = $pricePerNight * $nights;

            $uniqueRooms[$categoryId] = RoomAvailabilityData::from([
                'room_description' => $roomDescription,
                'price' => round($totalPrice, 2),
                'currency' => 'EUR',
            ]);
        }

        return $uniqueRooms;
    }
}
