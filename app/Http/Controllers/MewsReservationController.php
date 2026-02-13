<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Http\Responses\Interfaces\ApiResponseBuilderInterface;
use App\Interfaces\ReservationServiceInterface;
use Illuminate\Http\JsonResponse;

class MewsReservationController extends Controller
{
    public function __construct(
        private readonly ReservationServiceInterface $reservationService,
        private readonly ApiResponseBuilderInterface $responseBuilder
    ) {}

    /**
     * Get all reservations
     *
     * @param ReservationRequest $reservationRequest
     * @return JsonResponse
     */
    public function index(ReservationRequest $reservationRequest): JsonResponse
    {
        try {
            $reservations = $this->reservationService->getReservations($reservationRequest);

            return $this->responseBuilder->success($reservations);

        } catch (\App\Exceptions\MewsApiException $e) {
            return $this->responseBuilder->error(
                'mews_api_error',
                'Failed to retrieve reservations from property management system',
                502
            );

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'general_error',
                'An error occurred while retrieving reservations',
                500
            );
        }
    }
}
