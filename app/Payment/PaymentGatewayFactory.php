<?php

namespace App\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Payment\Gateways\MyFatoorahGateway;
use App\Payment\Gateways\TabbyGateway;
use App\Payment\Gateways\TamaraGateway;

class PaymentGatewayFactory
{
    /**
     * Create payment gateway instance
     */
    public static function make(string $gateway): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'myfatoorah' => app(MyFatoorahGateway::class),
            'tabby' => app(TabbyGateway::class),
            'tamara' => app(TamaraGateway::class),
            default => throw new \Exception("Unsupported payment gateway: {$gateway}")
        };
    }

    /**
     * Get all available gateways
     */
    public static function getAvailableGateways(): array
    {
        return ['myfatoorah', 'tabby', 'tamara'];
    }

    /**
     * Check if gateway is supported
     */
    public static function isSupported(string $gateway): bool
    {
        return in_array(strtolower($gateway), self::getAvailableGateways());
    }
}
