<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class FacilityRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255', Rule::unique('facilities', 'name')->ignore($this->facility?->id)],
        ];
    }
}
