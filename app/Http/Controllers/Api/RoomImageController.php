<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoomImageRequest;
use App\Http\Resources\RoomImageResource;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomImageController extends Controller
{
    use ApiResponse;

    public function store(RoomImageRequest $request, Room $room)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        $images = collect($request->file('images'))->map(function ($image) use ($room) {
            return RoomImage::create([
                'room_id' => $room->id,
                'image_url' => $image->store('rooms', 'public'),
            ]);
        });

        return $this->success(RoomImageResource::collection($images), 'Room images uploaded', 201);
    }

    public function destroy(Request $request, RoomImage $roomImage)
    {
        if ($request->user()->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        Storage::disk('public')->delete($roomImage->image_url);
        $roomImage->delete();

        return $this->success(null, 'Room image deleted');
    }
}
