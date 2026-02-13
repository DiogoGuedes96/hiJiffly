<?php

namespace App\Services;

use App\Clients\MewsReservationsEndpoint;
use App\Data\ReservationItemData;
use App\Data\ReservationResponseData;
use App\Exceptions\MewsApiException;
use App\Http\Requests\ReservationRequest;
use App\Interfaces\ReservationServiceInterface;
use App\Services\Shared\MewsTimeZoneService;
use Illuminate\Support\Facades\Log;

class ReservationService implements ReservationServiceInterface
{
    /**
     * Mapping of Mews states to our API states
     */
    private const STATE_MAPPING = [
        'Confirmed' => 'confirmed',
        'Optional' => 'pending',
        'Canceled' => 'cancelled',
        'Started' => 'confirmed',
        'Processed' => 'confirmed',
    ];

    /**
     * Mapping of Mews resource states to room_state
     */
    private const ROOM_STATE_MAPPING = [
        'Dirty' => 'unassigned',
        'Clean' => 'assigned',
        'Inspected' => 'assigned',
        'OutOfService' => 'unassigned',
        'OutOfOrder' => 'unassigned',
    ];

    public function __construct(
        private readonly MewsReservationsEndpoint $reservationsEndpoint,
        private readonly MewsTimeZoneService $mewsTimeZoneService,
    ) {}

    /**
     * Get all reservations filtered by request parameters
     *
     * @param ReservationRequest $reservationRequest
     * @return ReservationResponseData
     * @throws MewsApiException
     */
    public function getReservations(ReservationRequest $reservationRequest): ReservationResponseData
    {
        try {
            // Map status from our API to Mews states
            $mewsStates = $this->mapStatusToMewsStates($reservationRequest->status);
            
            // Convert dates to UTC
            $utcDates = $this->mewsTimeZoneService->convertDatesToUtcForReservations($reservationRequest->check_in, $reservationRequest->check_out);
            
            // Fetch reservations from Mews
            $mewsData = $this->reservationsEndpoint->getAll(
                $reservationRequest->property_id,
                $mewsStates,
                $utcDates['startUtc'],
                $utcDates['endUtc']
            );
            
            // Process and map reservations
            $reservations = $this->processReservations($mewsData);
            
            return ReservationResponseData::from([
                'property_id' => $reservationRequest->property_id,
                'check_in' => $reservationRequest->check_in,
                'check_out' => $reservationRequest->check_out,
                'status' => $reservationRequest->status,
                'reservations' => $reservations,
            ]);
            
        } catch (MewsApiException $e) {
            throw $e;
        }
    }

    /**
     * Map API status to Mews reservation states
     *
     * @param string|null $status
     * @return array
     */
    private function mapStatusToMewsStates(?string $status): array
    {
        if (!$status) {
            return ['Confirmed', 'Started', 'Processed']; // Default: active reservations
        }

        return match($status) {
            'confirmed' => ['Confirmed', 'Started', 'Processed'],
            'pending' => ['Optional'],
            'cancelled' => ['Canceled'],
            default => ['Confirmed', 'Started', 'Processed'],
        };
    }

    /**
     * Process Mews reservation data into our format
     *
     * @param array $mewsData
     * @return array
     */
    private function processReservations(array $mewsData): array
    {
        $reservations = [];
        
        $mewsReservations = $mewsData['Reservations'] ?? [];
        $customers = $this->indexById($mewsData['Customers'] ?? []);
        $resources = $this->indexById($mewsData['Resources'] ?? []);
        $resourceCategories = $this->indexById($mewsData['ResourceCategories'] ?? []);
        $services = $this->indexById($mewsData['Services'] ?? []);
        
        foreach ($mewsReservations as $reservation) {
            try {
                $reservationItem = $this->mapReservation(
                    $reservation,
                    $customers,
                    $resources,
                    $resourceCategories,
                    $services
                );
                
                if ($reservationItem) {
                    $reservations[] = $reservationItem;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return $reservations;
    }

    /**
     * Map single reservation to our format
     *
     * @param array $reservation
     * @param array $customers
     * @param array $resources
     * @param array $resourceCategories
     * @param array $services
     * @return ReservationItemData|null
     */
    private function mapReservation(
        array $reservation,
        array $customers,
        array $resources,
        array $resourceCategories,
        array $services
    ): ?ReservationItemData {
        // Get customer data
        $customerId = $reservation['CustomerId'] ?? null;
        $customer = $customers[$customerId] ?? null;
        
        if (!$customer) {
            return null; // Skip if no customer data
        }
        
        // Get resource (room) data
        $assignedResourceId = $reservation['AssignedResourceId'] ?? null;
        $resource = $resources[$assignedResourceId] ?? null;
        
        // Get category data
        $resourceCategoryId = $reservation['RequestedCategoryId'] ?? null;
        $category = $resourceCategories[$resourceCategoryId] ?? null;
        
        // Get service data
        $serviceId = $reservation['ServiceId'] ?? null;
        $service = $services[$serviceId] ?? null;
        
        // Map status
        $mewsState = $reservation['State'] ?? 'Confirmed';
        $status = self::STATE_MAPPING[$mewsState] ?? 'confirmed';
        
        // Determine room state
        $roomState = 'unassigned';
        if ($resource) {
            $resourceState = $resource['State'] ?? null;
            $roomState = self::ROOM_STATE_MAPPING[$resourceState] ?? 'assigned';
            
            // If reservation has started, mark as checked-in
            if ($mewsState === 'Started') {
                $roomState = 'checked-in';
            }
        }
        
        // Get room category name
        $categoryName = 'Unknown';
        if ($category && isset($category['Names']['en-US'])) {
            $categoryName = $category['Names']['en-US'];
        } elseif ($category && isset($category['Name'])) {
            $categoryName = $category['Name'];
        }
        
        // Get booking channel (origin)
        $bookingChannel = $reservation['Origin'] ?? 'Direct';
        
        // Format dates
        $checkIn = $this->formatDate($reservation['StartUtc'] ?? '');
        $checkOut = $this->formatDate($reservation['EndUtc'] ?? '');
        
        return ReservationItemData::from([
            'reservation_id' => $reservation['Id'],
            'status' => $status,
            'first_name' => $customer['FirstName'] ?? '',
            'last_name' => $customer['LastName'] ?? '',
            'email' => $customer['Email'] ?? '',
            'phone_number' => $customer['Phone'] ?? '',
            'booking_channel' => $bookingChannel,
            'room_state' => $roomState,
            'room_number' => $resource['Name'] ?? null,
            'room_type' => $service['Name'] ?? 'Unknown',
            'room_category' => $categoryName,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    /**
     * Index array by 'Id' field
     *
     * @param array $items
     * @return array
     */
    private function indexById(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            if (isset($item['Id'])) {
                $indexed[$item['Id']] = $item;
            }
        }
        return $indexed;
    }

    /**
     * Format UTC date string to YYYY-MM-DD
     *
     * @param string $utcDate
     * @return string
     */
    private function formatDate(string $utcDate): string
    {
        if (empty($utcDate)) {
            return '';
        }
        
        try {
            $date = new \DateTime($utcDate);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }
}

