<?php

namespace App\Http\Requests\Api\V1\AppointmentType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BaseAppointmentTypeRequest extends FormRequest
{
    public function mappedAttributes(): array
    {
        $attributeMap = [
            'data.attributes.name' => 'name',
            'data.attributes.description' => 'description',
            'data.attributes.duration_minutes' => 'duration_minutes',
        ];

        $attributesToUpdate = [];
        foreach ($attributeMap as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }

    public function messages(): array
    {
        return [
            'data.attributes.name.required' => 'The name field is required.',
            'data.attributes.name.string' => 'The name must be a string.',
            'data.attributes.description.string' => 'The description must be a string.',
            'data.attributes.duration_minutes.required' => 'The duration minutes field is required.',
            'data.attributes.duration_minutes.integer' => 'The duration minutes must be an integer.',
        ];
    }
}
