<?php

namespace App\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BasePaymentGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    protected $apiURL;
    protected $testMode;
    protected $currency;

    /**
     * Make HTTP request to gateway API
     */
    protected function makeRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        $url = $this->getFullUrl($endpoint);

        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)
                ->{strtolower($method)}($url, $data);

            $this->logRequest($endpoint, $data, $response->json());

            return $response->json();
        } catch (\Exception $e) {
            $this->logError($endpoint, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get full URL for endpoint
     */
    protected function getFullUrl(string $endpoint): string
    {
        // If it's already a full URL, return it
        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            return $endpoint;
        }

        return rtrim($this->apiURL, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Get HTTP headers
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Check if in test mode
     */
    protected function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Log API request
     */
    protected function logRequest(string $endpoint, array $data, $response): void
    {
        if (config('app.debug')) {
            Log::info("Payment Gateway Request [{$this->getGatewayName()}]", [
                'endpoint' => $endpoint,
                'data' => $data,
                'response' => $response,
            ]);
        }
    }

    /**
     * Log error
     */
    protected function logError(string $context, string $message): void
    {
        Log::error("Payment Gateway Error [{$this->getGatewayName()}]", [
            'context' => $context,
            'message' => $message,
        ]);
    }

    /**
     * Handle API error
     */
    protected function handleError($response): void
    {
        if (isset($response['IsSuccess']) && $response['IsSuccess'] === false) {
            $error = '';

            if (isset($response['ValidationErrors'])) {
                $error = collect($response['ValidationErrors'])
                    ->pluck('Error')
                    ->implode(', ');
            } elseif (isset($response['Message'])) {
                $error = $response['Message'];
            }

            throw new \Exception($error ?: 'Unknown payment gateway error');
        }
    }
}
