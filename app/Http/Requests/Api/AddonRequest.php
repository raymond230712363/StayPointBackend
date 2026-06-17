<?php

namespace App\Http\Requests\Api;

class AddonRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'price' => [$required, 'integer', 'min:0'],
        ];
    }
}
