<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Structured Errors
    |--------------------------------------------------------------------------
    |
    | When enabled, validation errors will be transformed into a structured
    | format with error codes and parameters for better frontend integration.
    |
    */

    'enable_structured_errors' => env('API_STRUCTURED_ERRORS', true),

    /*
    |--------------------------------------------------------------------------
    | Validation Mappings
    |--------------------------------------------------------------------------
    |
    | Map Laravel validation messages to error codes. The key is a pattern
    | to match in the validation message, and the value is the error code.
    |
    */

    'validation_mappings' => [
        'required' => 'required',
        'invalid' => 'invalid',
        'must be' => 'invalid_format',
        'format' => 'invalid_format',
        'email' => 'invalid_email',
        'unique' => 'already_exists',
        'exists' => 'not_found',
        'min' => 'too_short',
        'max' => 'too_long',
        'between' => 'out_of_range',
        'after' => 'must_be_after',
        'before' => 'must_be_before',
        'date' => 'invalid_date',
        'numeric' => 'must_be_numeric',
        'integer' => 'must_be_integer',
        'string' => 'must_be_string',
        'array' => 'must_be_array',
        'confirmed' => 'confirmation_mismatch',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Error Code
    |--------------------------------------------------------------------------
    |
    | The error code to use when no validation mapping matches.
    | Set to null to use the original validation message.
    |
    */

    'fallback_error_code' => 'validation_failed',

    /*
    |--------------------------------------------------------------------------
    | Extract Parameters
    |--------------------------------------------------------------------------
    |
    | When enabled, the response builder will attempt to extract parameters
    | from validation messages (like min, max, reference fields) and include
    | them in the response for better frontend error handling.
    |
    */

    'extract_parameters' => env('API_EXTRACT_PARAMETERS', true),

];
