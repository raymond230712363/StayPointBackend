<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Room;

class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::with(['rooms.images', 'rooms.facilities', 'rooms.reviews'])
            ->latest()
            ->get()
            ->map(function (Hotel $hotel) {
                $hotel->rooms->each(fn (Room $room) => $this->appendRating($room));
                return $hotel;
            });

        return response()->json([
            'success' => true,
            'hotels' => $hotels,
        ]);
    }

    public function show(Hotel $hotel)
    {
        $hotel->load(['rooms.images', 'rooms.facilities', 'rooms.reviews.user']);
        $hotel->rooms->each(fn (Room $room) => $this->appendRating($room));

        return response()->json([
            'success' => true,
            'hotel' => $hotel,
        ]);
    }

    public function room(Room $room)
    {
        $room->load(['hotel', 'images', 'facilities', 'reviews.user']);
        $this->appendRating($room);

        return response()->json([
            'success' => true,
            'room' => $room,
        ]);
    }

    private function appendRating(Room $room): void
    {
        $room->average_rating = round((float) $room->reviews->avg('rating'), 1);
        $room->review_count = $room->reviews->count();
    }
}
