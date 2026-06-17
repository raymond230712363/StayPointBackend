<?php

namespace App\Http\Requests\Api;

class RoomImageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
