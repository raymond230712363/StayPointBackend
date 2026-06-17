<?php

namespace App\Http\Requests\Api;

class RoomRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'hotel_id' => [$required, 'exists:hotels,id'],
            'room_name' => [$required, 'string', 'max:255'],
            'description' => [$required, 'string'],
            'capacity' => [$required, 'integer', 'min:1'],
            'price_per_night' => [$required, 'integer', 'min:0'],
            'stock' => [$required, 'integer', 'min:0'],
            'facility_ids' => ['sometimes', 'array'],
            'facility_ids.*' => ['exists:facilities,id'],
        ];
    }
}
