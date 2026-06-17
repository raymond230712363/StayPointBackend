<?php

namespace App\Http\Requests\Api;

class ReviewRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'booking_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'exists:bookings,id'],
            'rating' => [$required, 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
            'photo' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
