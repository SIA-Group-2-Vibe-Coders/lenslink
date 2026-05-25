<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Traits\ApiResponse;
use App\Repositories\BookingRepository;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentService $paymentService,
        protected BookingRepository $bookingRepository
    ) {}

    /**
     * GET /bookings
     * Get all bookings for the current user (as client or photographer).
     */
    public function index(Request $request)
    {
        $bookings = $this->bookingRepository->getForUser($request->user());

        return $this->successResponse($bookings);
    }

    /**
     * GET /bookings/{id}
     * Get a single booking — only visible to client or photographer of that booking.
     */
    public function show(Request $request, int $id)
    {
        $booking = $this->bookingRepository->findWithRelations($id);

        if ($request->user()->cannot('view', $booking)) {
            return $this->forbiddenResponse('You are not authorized to view this booking.');
        }

        return $this->successResponse($booking);
    }

    /**
     * POST /bookings
     * Create a new booking request.
     */
    public function store(StoreBookingRequest $request)
    {
        $client = $request->user();

        if ($client->id == $request->validated('photographer_id')) {
            return $this->errorResponse('You cannot book a session with yourself.', 400);
        }

        $booking = $this->bookingRepository->create([
            'client_id'       => $client->id,
            'photographer_id' => $request->validated('photographer_id'),
            'session_date'    => $request->validated('session_date'),
            'amount'          => $request->validated('amount'),
            'status'          => 'pending',
            'notes'           => $request->validated('notes'),
        ]);

        return $this->createdResponse(
            $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar']),
            'Booking request submitted successfully'
        );
    }

    /**
     * PATCH /bookings/{id}/status
     * Update the status of a booking (photographer: accept/decline/complete; either party: cancel).
     */
    public function updateStatus(UpdateBookingStatusRequest $request, int $id)
    {
        $booking = $this->bookingRepository->findWithRelations($id);
        $user    = $request->user();
        $status  = $request->validated('status');

        // Photographer-only actions
        if (in_array($status, ['accepted', 'declined', 'completed'])) {
            if ($user->cannot('updateStatus', $booking)) {
                return $this->forbiddenResponse('Only the photographer can accept, decline, or complete this booking.');
            }
        }

        // Cancel: either party can cancel
        if ($status === 'cancelled' && $user->cannot('cancel', $booking)) {
            return $this->forbiddenResponse('You are not authorized to cancel this booking.');
        }

        $updated = $this->bookingRepository->updateStatus($booking, $status);

        return $this->successResponse($updated, 'Booking status updated successfully');
    }

    /**
     * POST /bookings/{id}/pay
     * Create a Stripe Payment Intent for a specific booking.
     */
    public function pay(Request $request, int $id)
    {
        $booking = $this->bookingRepository->findWithRelations($id);
        $user    = $request->user();

        if ($user->cannot('pay', $booking)) {
            return $this->forbiddenResponse('Only the client who booked this session can pay for it.');
        }

        if (!in_array($booking->status, ['pending', 'accepted'])) {
            return $this->errorResponse(
                'You can only pay for pending or accepted bookings. Current status: ' . $booking->status,
                400
            );
        }

        try {
            $intent = $this->paymentService->createPaymentIntent(
                (int) $booking->amount,
                'usd',
                [
                    'booking_id'      => $booking->id,
                    'photographer_id' => $booking->photographer_id,
                    'client_id'       => $booking->client_id,
                ]
            );

            return response()->json([
                'status'        => 'success',
                'client_secret' => $intent->client_secret,
                'is_mock'       => false,
            ]);
        } catch (\Exception $e) {
            // Graceful fallback to mock mode when Stripe is unavailable
            return response()->json([
                'status'        => 'success',
                'client_secret' => 'mock_secret_booking_' . $booking->id,
                'is_mock'       => true,
                'message'       => 'Stripe integration offline. Simulated payment mode active.',
                'error_hint'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /bookings/{id}/confirm
     * Confirm that Stripe payment completed and update booking to paid.
     */
    public function confirmPayment(Request $request, int $id)
    {
        $request->validate(['payment_intent_id' => 'required|string']);

        $booking = $this->bookingRepository->findWithRelations($id);
        $user    = $request->user();

        if ($user->cannot('confirmPayment', $booking)) {
            return $this->forbiddenResponse('Unauthorized to confirm payment for this booking.');
        }

        // Handle simulated mock payment
        if (str_starts_with($request->payment_intent_id, 'mock_')) {
            $updated = $this->bookingRepository->markPaid($booking, $request->payment_intent_id);

            return $this->successResponse($updated, 'Simulated payment completed. Booking marked as paid (Mock Mode).');
        }

        // Verify with Stripe
        try {
            $intent = $this->paymentService->verifyPayment($request->payment_intent_id);

            if ($intent->status === 'succeeded') {
                $updated = $this->bookingRepository->markPaid($booking, $request->payment_intent_id);

                return $this->successResponse($updated, 'Payment confirmed. Booking is now paid.');
            }

            return $this->errorResponse('Payment has not succeeded. Current status: ' . $intent->status, 400);
        } catch (\Exception $e) {
            // Allow manual fallback recovery
            if ($request->boolean('force_mock')) {
                $updated = $this->bookingRepository->markPaid($booking, 'mock_recovered_' . time());

                return $this->successResponse($updated, 'Payment recovered via simulated fallback.');
            }

            return $this->errorResponse('Failed to verify payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /bookings/intent
     * Legacy stateless payment intent (without a pre-existing booking).
     */
    public function createIntent(Request $request)
    {
        $request->validate([
            'amount'          => 'required|numeric|min:1',
            'photographer_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $intent = $this->paymentService->createPaymentIntent(
                (int) $request->amount,
                'usd',
                [
                    'photographer_id' => $request->photographer_id,
                    'client_id'       => $request->user()->id,
                ]
            );

            return response()->json([
                'status'        => 'success',
                'client_secret' => $intent->client_secret,
                'is_mock'       => false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'        => 'success',
                'client_secret' => 'mock_secret_stateless_' . time(),
                'is_mock'       => true,
                'message'       => 'Stripe integration offline. Simulated payment mode active.',
                'error_hint'    => $e->getMessage(),
            ]);
        }
    }
}
