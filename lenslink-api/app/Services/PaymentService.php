<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Payment Intent for a booking.
     */
    public function createPaymentIntent(int $amount, string $currency = 'usd', array $metadata = [])
    {
        return PaymentIntent::create([
            'amount' => $amount * 100, // Stripe uses cents
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Verify a payment intent status.
     */
    public function verifyPayment(string $paymentIntentId)
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }
}
