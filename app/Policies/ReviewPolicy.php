<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function modify(User $user, Review $review): bool
    {
        return $user->role === 'admin' || $review->user_id === $user->id;
    }
}
