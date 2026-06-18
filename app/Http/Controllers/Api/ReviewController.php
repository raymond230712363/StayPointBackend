<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'room.hotel', 'booking'])->latest();

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
            $query->where('user_id', optional($user)->id);
        }

        return response()->json([
            'success' => true,
            'reviews' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:user_id|email',
            'user_id' => 'required_without:email|exists:users,id',
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $this->userFromRequest($request);
        $booking = Booking::with('review')->findOrFail($request->booking_id);
        $guard = $this->guardReviewAccess($user, $booking);
        if ($guard) {
            return $guard;
        }

        if ($booking->review) {
            return response()->json(['success' => false, 'message' => 'Booking ini sudah direview.'], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'room_id' => $booking->room_id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'photo' => $this->storePhoto($request),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dikirim',
            'review' => $review->load(['user', 'room.hotel', 'booking']),
        ], 201);
    }

    public function update(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:user_id|email',
            'user_id' => 'required_without:email|exists:users,id',
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $this->userFromRequest($request);
        if (!$user || $review->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Review tidak ditemukan.'], 404);
        }

        $review->rating = $request->input('rating', $review->rating);
        $review->comment = $request->input('comment', $review->comment);

        if ($request->hasFile('photo')) {
            if ($review->photo) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $review->photo));
            }
            $review->photo = $this->storePhoto($request);
        }

        $review->save();

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil diperbarui',
            'review' => $review->load(['user', 'room.hotel', 'booking']),
        ]);
    }

    public function destroy(Request $request, Review $review)
    {
        $user = $this->userFromRequest($request);
        if (!$user || $review->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Review tidak ditemukan.'], 404);
        }

        if ($review->photo) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $review->photo));
        }
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dihapus',
        ]);
    }

    public function summary(Room $room)
    {
        return response()->json([
            'success' => true,
            'room_id' => $room->id,
            'average_rating' => round((float) $room->reviews()->avg('rating'), 1),
            'review_count' => $room->reviews()->count(),
        ]);
    }

    private function userFromRequest(Request $request): ?User
    {
        if ($request->filled('user_id')) {
            return User::find($request->user_id);
        }

        return User::where('email', $request->email)->first();
    }

    private function guardReviewAccess(?User $user, Booking $booking)
    {
        if (!$user || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Review hanya bisa dibuat setelah booking completed.'], 422);
        }

        return null;
    }

    private function storePhoto(Request $request): ?string
    {
        if (!$request->hasFile('photo')) {
            return null;
        }

        return Storage::url($request->file('photo')->store('reviews', 'public'));
    }
}
