<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'room_name' => $this->room_name,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'price_per_night' => $this->price_per_night,
            'stock' => $this->stock,
            'average_rating' => round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating')), 2),
            'images' => RoomImageResource::collection($this->whenLoaded('images')),
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
