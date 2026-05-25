<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository
{
    /**
     * Get all bookings for a user (as client or photographer).
     */
    public function getForUser(User $user): Collection
    {
        return Booking::with(['client:id,name,avatar,email', 'photographer:id,name,avatar,email'])
            ->where('client_id', $user->id)
            ->orWhere('photographer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a booking by ID with related client and photographer.
     */
    public function findWithRelations(int $id): Booking
    {
        return Booking::with([
            'client:id,name,avatar,email',
            'photographer:id,name,avatar,email,specialty,location,price_range',
        ])->findOrFail($id);
    }

    /**
     * Create a new booking.
     */
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * Update a booking's status.
     */
    public function updateStatus(Booking $booking, string $status): Booking
    {
        $booking->update(['status' => $status]);
        return $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar']);
    }

    /**
     * Mark a booking as paid.
     */
    public function markPaid(Booking $booking, string $paymentIntentId): Booking
    {
        $booking->update([
            'status'            => 'paid',
            'stripe_payment_id' => $paymentIntentId,
        ]);
        return $booking->load(['client:id,name,avatar', 'photographer:id,name,avatar']);
    }
}
