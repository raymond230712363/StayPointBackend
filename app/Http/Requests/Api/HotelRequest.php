<?php

namespace App\Http\Requests\Api;

class HotelRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'location' => [$required, 'string', 'max:255'],
            'description' => [$required, 'string'],
            'thumbnail' => [$required, 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
