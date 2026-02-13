<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Http\Responses\Interfaces\ApiResponseBuilderInterface;
use App\Interfaces\MewsAvailabilityServiceInterface;
use Illuminate\Http\JsonResponse;

class MewsAvailabilityController extends Controller
{
    public function __construct(
        private readonly MewsAvailabilityServiceInterface $mewsAvailabilityService,
        private readonly ApiResponseBuilderInterface $responseBuilder
    ) {}

    /**
     * Get available rooms for the given parameters
     *
     * @param AvailabilityRequest $request
     * @return JsonResponse
     */
    public function index(AvailabilityRequest $request): JsonResponse
    {
        try {
            $availability = $this->mewsAvailabilityService->getAvailability($request);

            return $this->responseBuilder->success($availability);

        } catch (\App\Exceptions\InvalidDateRangeException $e) {
            return $this->responseBuilder->error(
                'invalid_date_range',
                $e->getMessage(),
                422
            );

        } catch (\App\Exceptions\MewsApiException $e) {
            return $this->responseBuilder->error(
                'mews_api_error',
                'Failed to retrieve availability from property management system',
                502
            );

        } catch (\Exception $e) {
            return $this->responseBuilder->error(
                'general_error',
                'An error occurred while retrieving available rooms',
                500
            );
        }
    }
}
