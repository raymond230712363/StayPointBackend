<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class BookingStatusRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'payment_status' => ['sometimes', Rule::in(['pending', 'paid', 'cancelled'])],
        ];
    }
}
