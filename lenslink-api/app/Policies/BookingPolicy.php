<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine if the user can view a booking.
     * Only the client or photographer of the booking can view it.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            || $user->id === $booking->photographer_id;
    }

    /**
     * Determine if the user can update a booking's status (accept/decline/complete).
     * Only the photographer can accept, decline, or mark as complete.
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->id === $booking->photographer_id;
    }

    /**
     * Determine if the user can cancel a booking.
     * Either the client or the photographer can cancel.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            || $user->id === $booking->photographer_id;
    }

    /**
     * Determine if the user can pay for a booking.
     * Only the client who created the booking can pay.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id;
    }

    /**
     * Determine if the user can confirm payment for a booking.
     * Only the client who created the booking can confirm.
     */
    public function confirmPayment(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id;
    }
}
