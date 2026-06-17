<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $rooms = Room::with(['hotel', 'images', 'facilities'])
            ->withAvg('reviews', 'rating')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('room_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->hotel_id, fn ($query, $hotelId) => $query->where('hotel_id', $hotelId))
            ->when($request->min_capacity, fn ($query, $capacity) => $query->where('capacity', '>=', $capacity))
            ->when($request->min_price, fn ($query, $price) => $query->where('price_per_night', '>=', $price))
            ->when($request->max_price, fn ($query, $price) => $query->where('price_per_night', '<=', $price))
            ->when($request->facility_id, fn ($query, $facilityId) => $query->whereHas('facilities', fn ($q) => $q->where('facilities.id', $facilityId)))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        $rooms->setCollection($rooms->getCollection()->map(fn ($room) => (new RoomResource($room))->resolve()));

        return $this->success($rooms);
    }

    public function store(RoomRequest $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();
        $facilityIds = $data['facility_ids'] ?? [];
        unset($data['facility_ids']);

        $room = Room::create($data);
        $room->facilities()->sync($facilityIds);

        return $this->success(new RoomResource($room->load(['hotel', 'images', 'facilities'])), 'Room created', 201);
    }

    public function show(Room $room)
    {
        return $this->success(new RoomResource($room->load(['hotel', 'images', 'facilities', 'reviews.user'])->loadAvg('reviews', 'rating')));
    }

    public function update(RoomRequest $request, Room $room)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();
        $facilityIds = $data['facility_ids'] ?? null;
        unset($data['facility_ids']);

        $room->update($data);

        if (is_array($facilityIds)) {
            $room->facilities()->sync($facilityIds);
        }

        return $this->success(new RoomResource($room->load(['hotel', 'images', 'facilities'])), 'Room updated');
    }

    public function destroy(Request $request, Room $room)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $room->delete();

        return $this->success(null, 'Room deleted');
    }
}
