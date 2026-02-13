<?php

namespace App\Clients;

use App\Clients\Contracts\MewsClientInterface;
use App\Exceptions\MewsApiException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

abstract class MewsClient implements MewsClientInterface
{
    protected string $baseUrl;
    protected string $clientToken;
    protected string $accessToken;
    protected string $client;
    protected int $timeout;
    protected int $retryTimes;

    public function __construct()
    {
        $this->baseUrl = config('mews.api_base_url');
        $this->clientToken = config('mews.client_token');
        $this->accessToken = config('mews.access_token');
        $this->client = config('mews.mews_client');
        $this->timeout = config('mews.timeout', 30);
        $this->retryTimes = config('mews.retry_times', 3);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    /**
     * Make an HTTP POST request to Mews API
     *
     * @param string $endpoint
     * @param array $data
     * @return Response
     * @throws MewsApiException
     */
    protected function post(string $endpoint, array $data = []): Response
    {
        $requestData = array_merge([
            'ClientToken' => $this->clientToken,
            'AccessToken' => $this->accessToken,
            'Client' => $this->client,
        ], $data);

        try {
            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, 100)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}{$endpoint}", $requestData);

            // Check if response failed and throw MewsApiException
            if ($response->failed()) {
                throw new MewsApiException(
                    "Mews API request failed: {$endpoint}",
                    $response->status()
                );
            }

            return $response;

        } catch (RequestException $e) {
            throw new MewsApiException(
                "Mews API request failed: {$endpoint}",
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Get common request body with credentials
     *
     * @return array
     */
    protected function getBaseRequestBody(): array
    {
        return [
            'ClientToken' => $this->clientToken,
            'AccessToken' => $this->accessToken,
            'Client' => $this->client,
        ];
    }
}
