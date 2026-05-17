<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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
     * Get bookings associated with the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get bookings where current user is either client OR photographer
        $bookings = Booking::with(['client:id,name,avatar,email', 'photographer:id,name,avatar,email'])
            ->where('client_id', $user->id)
            ->orWhere('photographer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings
        ]);
    }

    /**
     * Create a new booking request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'photographer_id' => 'required|exists:users,id',
            'session_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $client = $request->user();

        if ($client->id == $request->photographer_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot book a session with yourself.'
            ], 400);
        }

        $booking = Booking::create([
            'client_id' => $client->id,
            'photographer_id' => $request->photographer_id,
            'session_date' => $request->session_date,
            'amount' => $request->amount,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking request submitted successfully',
            'data' => $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar'])
        ]);
    }

    /**
     * Update the status of a booking.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined,completed,cancelled',
        ]);

        $booking = Booking::findOrFail($id);
        $user = $request->user();

        // Check authorization:
        // Photographer can accept, decline, complete
        // Client or Photographer can cancel
        if (in_array($request->status, ['accepted', 'declined', 'completed'])) {
            if ($booking->photographer_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only the photographer can accept, decline, or complete this booking.'
                ], 403);
            }
        }

        if ($request->status === 'cancelled') {
            if ($booking->client_id !== $user->id && $booking->photographer_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to cancel this booking.'
                ], 403);
            }
        }

        $booking->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking status updated successfully',
            'data' => $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar'])
        ]);
    }

    /**
     * POST /api/bookings/{id}/pay
     * Create a Stripe Payment Intent specifically for this booking.
     */
    public function pay(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $user = $request->user();

        if ($booking->client_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only the client who booked this session can pay for it.'
            ], 403);
        }

        if ($booking->status !== 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only pay for accepted bookings. Current status: ' . $booking->status
            ], 400);
        }

        // Generate payment intent
        $intent = $this->paymentService->createPaymentIntent(
            (int) $booking->amount,
            'usd',
            [
                'booking_id' => $booking->id,
                'photographer_id' => $booking->photographer_id,
                'client_id' => $booking->client_id
            ]
        );

        return response()->json([
            'status' => 'success',
            'client_secret' => $intent->client_secret,
        ]);
    }

    /**
     * POST /api/bookings/{id}/confirm
     * Confirm that Stripe payment has been completed and update status.
     */
    public function confirmPayment(Request $request, $id)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $booking = Booking::findOrFail($id);
        $user = $request->user();

        if ($booking->client_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to confirm payment for this booking.'
            ], 403);
        }

        // Verify the payment state with Stripe
        try {
            $intent = $this->paymentService->verifyPayment($request->payment_intent_id);

            if ($intent->status === 'succeeded') {
                $booking->update([
                    'status' => 'paid',
                    'stripe_payment_id' => $request->payment_intent_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment confirmed successfully. Booking is now paid.',
                    'data' => $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar'])
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment has not succeeded. Current status: ' . $intent->status
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
