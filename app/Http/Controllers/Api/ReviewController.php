<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(ReviewRequest $request)
    {
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->status !== 'completed') {
            return $this->error('Only completed bookings can be reviewed', 422);
        }

        if ($booking->review()->exists()) {
            return $this->error('Booking already reviewed', 422);
        }

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['room_id'] = $booking->room_id;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('reviews', 'public');
        }

        $review = Review::create($data);

        return $this->success(new ReviewResource($review->load(['user', 'room'])), 'Review created', 201);
    }

    public function update(ReviewRequest $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();
        unset($data['booking_id']);

        if ($request->hasFile('photo')) {
            Storage::disk('public')->delete($review->photo);
            $data['photo'] = $request->file('photo')->store('reviews', 'public');
        }

        $review->update($data);

        return $this->success(new ReviewResource($review->load(['user', 'room'])), 'Review updated');
    }

    public function destroy(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        Storage::disk('public')->delete($review->photo);
        $review->delete();

        return $this->success(null, 'Review deleted');
    }

    public function roomReviews(Request $request, int $roomId)
    {
        $reviews = Review::with('user')
            ->where('room_id', $roomId)
            ->latest()
            ->paginate($request->integer('per_page', 10));
        $reviews->setCollection($reviews->getCollection()->map(fn ($review) => (new ReviewResource($review))->resolve()));

        return $this->success([
            'average_rating' => round((float) Review::where('room_id', $roomId)->avg('rating'), 2),
            'reviews' => $reviews,
        ]);
    }
}
