<?php

namespace App\Http\Responses\Interfaces;

use Illuminate\Http\JsonResponse;

/**
 * API Response Builder Interface
 *
 * Contract for building standardized API responses across the application.
 * This interface ensures consistent response formats and provides a clear
 * contract for response builders.
 *
 * @package App\Http\Responses\Interfaces
 * @author Hijiffly
 * @since 1.0.0
 */
interface ApiResponseBuilderInterface
{
    /**
     * Build a successful response
     *
     * @param mixed $data The data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     * @return JsonResponse
     */
    public function success($data, int $statusCode = 200): JsonResponse;

    /**
     * Build an error response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param array $details Additional error details
     * @return JsonResponse
     */
    public function error(string $errorCode, string $message, int $statusCode = 400, array $details = []): JsonResponse;

    /**
     * Build a not found response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function notFound(string $errorCode, string $message): JsonResponse;

    /**
     * Build a validation error response
     *
     * @param array $errors Validation errors
     * @param string $message General validation message
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse;

    /**
     * Build an unauthorized response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function unauthorized(string $errorCode, string $message): JsonResponse;

    /**
     * Build a forbidden response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function forbidden(string $errorCode, string $message): JsonResponse;
}
