<?php

namespace App\Http\Requests\Api;

class BookingRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'addons' => ['sometimes', 'array'],
            'addons.*.id' => ['required_with:addons', 'exists:addons,id'],
            'addons.*.quantity' => ['required_with:addons', 'integer', 'min:1'],
        ];
    }
}
