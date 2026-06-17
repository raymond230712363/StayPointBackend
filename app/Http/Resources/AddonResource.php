<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->whenPivotLoaded('booking_addons', fn () => $this->pivot->quantity),
            'subtotal' => $this->whenPivotLoaded('booking_addons', fn () => $this->pivot->subtotal),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
