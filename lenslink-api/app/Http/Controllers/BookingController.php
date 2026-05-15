<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * POST /api/bookings/intent
     * Create a payment intent for a session.
     */
    public function createIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'photographer_id' => 'required',
        ]);

        $intent = $this->paymentService->createPaymentIntent(
            $request->amount,
            'usd',
            ['photographer_id' => $request->photographer_id]
        );

        return response()->json([
            'status' => 'success',
            'client_secret' => $intent->client_secret,
        ]);
    }
}
