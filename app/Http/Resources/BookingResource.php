<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'room_id' => $this->room_id,
            'room' => new RoomResource($this->whenLoaded('room')),
            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),
            'total_nights' => $this->total_nights,
            'total_price' => $this->total_price,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'qr_code' => $this->qr_code ? Storage::url($this->qr_code) : null,
            'pdf_receipt' => $this->pdf_receipt ? Storage::url($this->pdf_receipt) : null,
            'addons' => AddonResource::collection($this->whenLoaded('addons')),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
