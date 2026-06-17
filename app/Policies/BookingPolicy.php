<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->role === 'admin' || $booking->user_id === $user->id;
    }

    public function updateStatus(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking) && $booking->status !== 'completed';
    }
}
