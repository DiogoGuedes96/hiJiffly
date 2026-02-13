<?php

namespace App\Http\Responses;

use App\Http\Responses\Interfaces\ApiResponseBuilderInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Structured API Response Builder
 *
 * Advanced implementation of ApiResponseBuilderInterface that builds
 * standardized JSON responses with structured error codes and
 * parametrized validation errors for better frontend integration.
 *
 * Uses configurable validation mappings to convert Laravel validation
 * messages into structured error codes while maintaining simplicity.
 *
 * @package App\Http\Responses
 * @author Hijiffly
 * @since 1.0.0
 */
class StructuredApiResponseBuilder implements ApiResponseBuilderInterface
{
    private const REQUEST_ID_HEADER = 'X-Request-Id';

    /**
     * Configuration for validation mappings and behavior
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('api_responses', [
            'enable_structured_errors' => true,
            'validation_mappings' => [],
            'fallback_error_code' => 'validation_failed',
            'extract_parameters' => true,
        ]);
    }

    /**
     * Build a successful response
     *
     * @param mixed $data The data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     * @return JsonResponse
     */
    public function success($data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'timestamp' => now()->toISOString(),
        ];

        // Handle Spatie Laravel Data
        if ($data !== null && $data instanceof \Spatie\LaravelData\Data) {
            $response['data'] = $data->toArray();
        } elseif ($data !== null && $data instanceof \Illuminate\Contracts\Support\Arrayable) {
            $response['data'] = $data->toArray();
        } else {
            $response['data'] = $data;
        }

        return $this->withRequestIdHeader(response()->json($response, $statusCode));
    }

    /**
     * Build an error response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param array $details Additional error details
     * @return JsonResponse
     */
    public function error(string $errorCode, string $message, int $statusCode = 400, array $details = []): JsonResponse
    {
        $response = [
            'success' => false,
            'error_code' => $errorCode,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return $this->withRequestIdHeader(response()->json($response, $statusCode));
    }

    /**
     * Add request ID header if available
     *
     * @param JsonResponse $response
     * @return JsonResponse
     */
    private function withRequestIdHeader(JsonResponse $response): JsonResponse
    {
        if (!app()->bound('request')) {
            return $response;
        }

        $request = request();
        $requestId = $request->attributes->get('request_id') ?? $request->headers->get(self::REQUEST_ID_HEADER);

        if (!is_string($requestId) || trim($requestId) === '') {
            return $response;
        }

        $requestId = trim($requestId);
        if (strlen($requestId) > 128) {
            return $response;
        }

        if (!preg_match('/^[A-Za-z0-9._\-]+$/', $requestId)) {
            return $response;
        }

        return $response->header(self::REQUEST_ID_HEADER, $requestId);
    }

    /**
     * Build a not found response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function notFound(string $errorCode, string $message): JsonResponse
    {
        return $this->error($errorCode, $message, 404);
    }

    /**
     * Build a validation error response with structured format
     *
     * @param array $errors Validation errors
     * @param string $message General validation message (error code for frontend translation)
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = 'errors.general.validation_error'): JsonResponse
    {
        if (!$this->config['enable_structured_errors']) {
            // Fallback to simple format
            return $this->error('validation_error', $message, 422, ['validation_errors' => $errors]);
        }

        $structuredErrors = $this->transformValidationErrors($errors);

        return $this->error(
            'validation_error',
            $message, // Frontend will translate this code
            422,
            ['validation_errors' => $structuredErrors]
        );
    }

    /**
     * Build an unauthorized response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function unauthorized(string $errorCode, string $message): JsonResponse
    {
        return $this->error($errorCode, $message, 401);
    }

    /**
     * Build a forbidden response
     *
     * @param string $errorCode Application-specific error code
     * @param string $message Human-readable error message
     * @return JsonResponse
     */
    public function forbidden(string $errorCode, string $message): JsonResponse
    {
        return $this->error($errorCode, $message, 403);
    }

    /**
     * Transform Laravel validation errors into structured error format
     *
     * @param array $errors Original Laravel validation errors
     * @return array Structured validation errors
     */
    protected function transformValidationErrors(array $errors): array
    {
        $structured = [];

        foreach ($errors as $field => $messages) {
            $structured[$field] = [];

            foreach ($messages as $message) {
                $errorCode = $this->generateErrorCode($field, $message);
                $params = $this->extractParams($field, $message);

                $errorItem = ['code' => $errorCode];

                if (!empty($params)) {
                    $errorItem['params'] = $params;
                }

                $structured[$field][] = $errorItem;
            }
        }

        return $structured;
    }

    /**
     * Generate error code based on field and message using configuration
     *
     * @param string $field Field name
     * @param string $message Validation message
     * @return string Error code
     */
    protected function generateErrorCode(string $field, string $message): string
    {
        // Convert snake_case to dot notation
        $fieldCode = str_replace('_', '.', $field);

        // If message already looks like an error code, use it directly
        if (str_starts_with($message, 'errors.')) {
            return $message;
        }

        $lowerMessage = strtolower($message);

        // Use configured validation mappings
        $mappings = $this->config['validation_mappings'] ?? [];

        foreach ($mappings as $pattern => $code) {
            if (str_contains($lowerMessage, strtolower($pattern))) {
                return "errors.{$fieldCode}.{$code}";
            }
        }

        // Use fallback if configured
        $fallback = $this->config['fallback_error_code'];
        if ($fallback) {
            return "errors.{$fieldCode}.{$fallback}";
        }

        // No fallback - return original message (for backwards compatibility)
        return $message;
    }

    /**
     * Extract parameters from validation message if enabled
     *
     * @param string $field Field name
     * @param string $message Validation message
     * @return array Parameters
     */
    protected function extractParams(string $field, string $message): array
    {
        if (!$this->config['extract_parameters']) {
            return [];
        }

        $params = [];

        // Extract numeric values for min/max validations
        if (preg_match('/(\d+)/', $message, $matches)) {
            $number = (int)$matches[1];

            if (str_contains(strtolower($message), 'at least') || str_contains(strtolower($message), 'minimum')) {
                $params['min'] = $number;
            } elseif (str_contains(strtolower($message), 'greater than') || str_contains(strtolower($message), 'maximum')) {
                $params['max'] = $number;
            }
        }

        // Extract reference fields for date comparisons
        if (str_contains($message, 'after') && preg_match('/after.+?(\w+)/', $message, $matches)) {
            $params['reference_field'] = $matches[1];
        }

        if (str_contains($message, 'before') && preg_match('/before.+?(\w+)/', $message, $matches)) {
            $params['reference_field'] = $matches[1];
        }

        return $params;
    }
}
