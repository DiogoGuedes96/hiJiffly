<?php

namespace App\Clients\Contracts;

interface MewsClientInterface
{
    /**
     * Get the base URL for the Mews API
     */
    public function getBaseUrl(): string;

    /**
     * Get the client token
     */
    public function getClientToken(): string;

    /**
     * Get the access token
     */
    public function getAccessToken(): string;

    /**
     * Get the client identifier
     */
    public function getClient(): string;
}
